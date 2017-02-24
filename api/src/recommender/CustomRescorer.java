/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package recommender;

import org.apache.mahout.cf.taste.impl.common.FastIDSet;
import org.apache.mahout.cf.taste.recommender.IDRescorer;

/**
 *
 * @author Daniele Cenni, daniele.cenni@unifi.it
 */
public class CustomRescorer implements IDRescorer {

    FastIDSet allowedIDs;

    public CustomRescorer(FastIDSet allowedIDs) {
        this.allowedIDs = allowedIDs;
    }

    @Override
    public double rescore(long id, double originalScore) {
        return originalScore;
    }

    @Override
    public boolean isFiltered(long id) {
        return !this.allowedIDs.contains(id);
    }

}
