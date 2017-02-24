/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package recommender;

import org.apache.mahout.cf.taste.common.TasteException;
import org.apache.mahout.cf.taste.eval.RecommenderBuilder;
import org.apache.mahout.cf.taste.impl.recommender.svd.ALSWRFactorizer;
import org.apache.mahout.cf.taste.model.DataModel;
import org.apache.mahout.cf.taste.recommender.Recommender;

/**
 *
 * @author Daniele Cenni, daniele.cenni@unifi.it
 */
public class MyRecommenderBuilder implements RecommenderBuilder {

    @Override
    public Recommender buildRecommender(DataModel dataModel) throws TasteException {
        /*UserSimilarity similarity = new PearsonCorrelationSimilarity(dataModel);
         UserNeighborhood neighborhood = new ThresholdUserNeighborhood(0.1, similarity, dataModel);
         return new GenericUserBasedRecommender(dataModel, neighborhood, similarity);*/
        ALSWRFactorizer factorizer = new ALSWRFactorizer(dataModel, 2, 0.025, 3);
        //return new SVDRecommender(dataModel, factorizer);
        return new MyRecommender(dataModel, factorizer);
    }
}
