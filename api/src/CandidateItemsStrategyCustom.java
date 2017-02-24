
import java.util.ArrayList;
import java.util.Collection;
import java.util.List;
import org.apache.mahout.cf.taste.common.Refreshable;
import org.apache.mahout.cf.taste.common.TasteException;
import org.apache.mahout.cf.taste.impl.common.FastIDSet;
import org.apache.mahout.cf.taste.model.DataModel;
import org.apache.mahout.cf.taste.model.PreferenceArray;
import org.apache.mahout.cf.taste.recommender.CandidateItemsStrategy;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 *
 * @author Daniele Cenni, daniele.cenni@unifi.it
 */
public class CandidateItemsStrategyCustom implements CandidateItemsStrategy {

    private DataModel dataModel;

    public CandidateItemsStrategyCustom(DataModel dataModel) {
        this.dataModel = dataModel;
    }

    @Override
    public FastIDSet getCandidateItems(long l, PreferenceArray preferencesFromUser, DataModel dm, boolean bln) throws TasteException {
        List<Long> specificlItemIDs = new ArrayList<>();
        FastIDSet candidateItemIDs = new FastIDSet();

        for (long itemID : specificlItemIDs) {
            candidateItemIDs.add(itemID);
        }

        for (int j = 0; j < preferencesFromUser.length(); j++) {
            candidateItemIDs.remove(preferencesFromUser.getItemID(j));
        }

        return candidateItemIDs;
    }

    @Override
    public void refresh(Collection<Refreshable> clctn) {
        throw new UnsupportedOperationException("Not supported yet."); //To change body of generated methods, choose Tools | Templates.
    }

}
