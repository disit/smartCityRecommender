/* Recommender
 Copyright (C) 2017 DISIT Lab http://www.disit.org - University of Florence 

 This program is free software: you can redistribute it and/or modify 
 it under the terms of the GNU Affero General Public License as 
 published by the Free Software Foundation, either version 3 of the 
 License, or (at your option) any later version. 

 This program is distributed in the hope that it will be useful, 
 but WITHOUT ANY WARRANTY; without even the implied warranty of 
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
 GNU Affero General Public License for more details. 

 You should have received a copy of the GNU Affero General Public License 
 along with this program.  If not, see <http://www.gnu.org/licenses/>. */
package smartcityrecommender;

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
