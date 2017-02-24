/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package recommender;

import java.io.IOException;
import java.util.Collection;
import java.util.List;
import java.util.concurrent.Callable;
import com.google.common.base.Preconditions;
import com.mysql.jdbc.jdbc2.optional.MysqlDataSource;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Properties;
import java.util.concurrent.Executors;
import java.util.concurrent.TimeUnit;
import java.util.logging.Level;
import org.apache.mahout.cf.taste.common.Refreshable;
import org.apache.mahout.cf.taste.common.TasteException;
import org.apache.mahout.cf.taste.impl.common.FastIDSet;
import org.apache.mahout.cf.taste.impl.common.RefreshHelper;
import org.apache.mahout.cf.taste.impl.model.jdbc.MySQLJDBCDataModel;
import org.apache.mahout.cf.taste.impl.model.jdbc.ReloadFromJDBCDataModel;
import org.apache.mahout.cf.taste.impl.recommender.AbstractRecommender;
import org.apache.mahout.cf.taste.impl.recommender.AllUnknownItemsCandidateItemsStrategy;
import org.apache.mahout.cf.taste.impl.recommender.TopItems;
import org.apache.mahout.cf.taste.impl.recommender.svd.ALSWRFactorizer;
import org.apache.mahout.cf.taste.impl.recommender.svd.Factorization;
import org.apache.mahout.cf.taste.impl.recommender.svd.Factorizer;
import org.apache.mahout.cf.taste.impl.recommender.svd.NoPersistenceStrategy;
import org.apache.mahout.cf.taste.impl.recommender.svd.PersistenceStrategy;
import org.apache.mahout.cf.taste.impl.recommender.svd.SVDRecommender;
import org.apache.mahout.cf.taste.model.DataModel;
import org.apache.mahout.cf.taste.model.JDBCDataModel;
import org.apache.mahout.cf.taste.model.PreferenceArray;
import org.apache.mahout.cf.taste.recommender.CandidateItemsStrategy;
import org.apache.mahout.cf.taste.recommender.IDRescorer;
import org.apache.mahout.cf.taste.recommender.RecommendedItem;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import static recommender.Recommender.getRecommendations;
import static recommender.Recommender.getRecommendationsJSON;
import static recommender.Recommender.init;
import static recommender.Recommender.loadCategories;
import static recommender.Recommender.loadGroups;
import static recommender.Recommender.loadGroupsLangs;
import static recommender.Recommender.loadSettings;
import static recommender.Recommender.loadUsersProfiles;
import static recommender.Recommender.setUserPreferences;
import static recommender.Recommender.updateUserProfile;

/**
 * @author Daniele Cenni, daniele.cenni@unifi.it
 * {@link org.apache.mahout.cf.taste.recommender.Recommender} that uses matrix
 * factorization (a projection of users and items onto a feature space)
 */
public final class CustomRecommender extends AbstractRecommender {

    private Factorization factorization;
    private final Factorizer factorizer;
    private final PersistenceStrategy persistenceStrategy;
    private final RefreshHelper refreshHelper;
    private static SVDRecommender svdRecommender;

    private static final Logger log = LoggerFactory.getLogger(CustomRecommender.class);

    public CustomRecommender(DataModel dataModel, Factorizer factorizer) throws TasteException {
        this(dataModel, factorizer, new AllUnknownItemsCandidateItemsStrategy(), getDefaultPersistenceStrategy());
    }

    public CustomRecommender(DataModel dataModel, Factorizer factorizer, CandidateItemsStrategy candidateItemsStrategy)
            throws TasteException {
        this(dataModel, factorizer, candidateItemsStrategy, getDefaultPersistenceStrategy());
    }

    /**
     * Create a custom SVDRecommender using a persistent store to cache
     * factorizations. A factorization is loaded from the store if present,
     * otherwise a new factorization is computed and saved in the store.
     *
     * The {@link #refresh(java.util.Collection) refresh} method recomputes the
     * factorization and overwrites the store.
     *
     * @param dataModel
     * @param factorizer
     * @param persistenceStrategy
     * @throws TasteException
     * @throws IOException
     */
    public CustomRecommender(DataModel dataModel, Factorizer factorizer, PersistenceStrategy persistenceStrategy)
            throws TasteException {
        this(dataModel, factorizer, getDefaultCandidateItemsStrategy(), persistenceStrategy);
    }

    /**
     * Create a custom SVDRecommender using a persistent store to cache
     * factorizations. A factorization is loaded from the store if present,
     * otherwise a new factorization is computed and saved in the store.
     *
     * The {@link #refresh(java.util.Collection) refresh} method recomputes the
     * factorization and overwrites the store.
     *
     * @param dataModel
     * @param factorizer
     * @param candidateItemsStrategy
     * @param persistenceStrategy
     *
     * @throws TasteException
     */
    public CustomRecommender(DataModel dataModel, Factorizer factorizer, CandidateItemsStrategy candidateItemsStrategy,
            PersistenceStrategy persistenceStrategy) throws TasteException {
        super(dataModel, candidateItemsStrategy);
        this.factorizer = Preconditions.checkNotNull(factorizer);
        this.persistenceStrategy = Preconditions.checkNotNull(persistenceStrategy);
        try {
            factorization = persistenceStrategy.load();
        } catch (IOException e) {
            throw new TasteException("Error loading factorization", e);
        }

        if (factorization == null) {
            train();
        }

        refreshHelper = new RefreshHelper(new Callable<Object>() {
            @Override
            public Object call() throws TasteException {
                train();
                return null;
            }
        });
        refreshHelper.addDependency(getDataModel());
        refreshHelper.addDependency(factorizer);
        refreshHelper.addDependency(candidateItemsStrategy);
    }

    static PersistenceStrategy getDefaultPersistenceStrategy() {
        return new NoPersistenceStrategy();
    }

    private void train() throws TasteException {
        factorization = factorizer.factorize();
        try {
            persistenceStrategy.maybePersist(factorization);
        } catch (IOException e) {
            throw new TasteException("Error persisting factorization", e);
        }
    }

    @Override
    public List<RecommendedItem> recommend(long userID, int howMany, IDRescorer rescorer, boolean includeKnownItems)
            throws TasteException {
        Preconditions.checkArgument(howMany >= 1, "howMany must be at least 1");
        log.debug("Recommending items for user ID '{}'", userID);

        PreferenceArray preferencesFromUser = getDataModel().getPreferencesFromUser(userID);
        FastIDSet possibleItemIDs = getAllOtherItems(userID, preferencesFromUser, includeKnownItems);

        List<RecommendedItem> topItems = TopItems.getTopItems(howMany, possibleItemIDs.iterator(), rescorer,
                new Estimator(userID));
        log.debug("Recommendations are: {}", topItems);

        return topItems;
    }

    /**
     * a preference is estimated by computing the dot-product of the user and
     * item feature vectors
     */
    @Override
    public float estimatePreference(long userID, long itemID) throws TasteException {
        double[] userFeatures = factorization.getUserFeatures(userID);
        double[] itemFeatures = factorization.getItemFeatures(itemID);
        double estimate = 0;
        for (int feature = 0; feature < userFeatures.length; feature++) {
            estimate += userFeatures[feature] * itemFeatures[feature];
        }
        return (float) estimate;
    }

    private final class Estimator implements TopItems.Estimator<Long> {

        private final long theUserID;

        private Estimator(long theUserID) {
            this.theUserID = theUserID;
        }

        @Override
        public double estimate(Long itemID) throws TasteException {
            return estimatePreference(theUserID, itemID);
        }
    }

    /**
     * Refresh the data model and factorization.
     */
    @Override
    public void refresh(Collection<Refreshable> alreadyRefreshed) {
        refreshHelper.refresh(alreadyRefreshed);
    }

}
