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
/*
 * http://kickstarthadoop.blogspot.it/2011/05/evaluating-mahout-based-recommender.html
 */
package smartcityrecommender;

import com.mysql.jdbc.jdbc2.optional.MysqlDataSource;
import org.apache.mahout.cf.taste.common.TasteException;
import org.apache.mahout.cf.taste.eval.RecommenderBuilder;
import org.apache.mahout.cf.taste.eval.RecommenderEvaluator;
import org.apache.mahout.cf.taste.impl.eval.RMSRecommenderEvaluator;
import org.apache.mahout.cf.taste.impl.model.jdbc.MySQLJDBCDataModel;
import org.apache.mahout.cf.taste.impl.neighborhood.NearestNUserNeighborhood;
import org.apache.mahout.cf.taste.impl.recommender.svd.ALSWRFactorizer;
import org.apache.mahout.cf.taste.impl.similarity.TanimotoCoefficientSimilarity;
import org.apache.mahout.cf.taste.model.DataModel;
import org.apache.mahout.cf.taste.neighborhood.UserNeighborhood;
import org.apache.mahout.cf.taste.recommender.Recommender;
import org.apache.mahout.cf.taste.similarity.UserSimilarity;
import org.apache.mahout.common.RandomUtils;

/**
 *
 * @author Daniele Cenni, daniele.cenni@unifi.it
 */
public class MyRecommenderEvaluator {

    private static int neighbourhoodSize = 7;

    public static void main(String args[]) {
        //String recsFile = "D://inputData.txt";

        /*creating a RecommenderBuilder Objects with overriding the buildRecommender method
         this builder object is used as one of the parameters for RecommenderEvaluator - evaluate method*/
        //for Recommendation evaluations
        RecommenderBuilder userSimRecBuilder = new RecommenderBuilder() {
            @Override
            public Recommender buildRecommender(DataModel model) throws TasteException {
                //The Similarity algorithms used in your recommender
                UserSimilarity userSimilarity = new TanimotoCoefficientSimilarity(model);

                /*The Neighborhood algorithms used in your recommender
                 not required if you are evaluating your item based recommendations*/
                UserNeighborhood neighborhood = new NearestNUserNeighborhood(neighbourhoodSize, userSimilarity, model);
                //Recommender used in your real time implementation
                //Recommender recommender = new GenericBooleanPrefUserBasedRecommender(model, neighborhood, userSimilarity);
                //return recommender;
                ALSWRFactorizer factorizer = new ALSWRFactorizer(model, 2, 0.025, 3);
                return new MyRecommender(model, factorizer);
            }
        };

        try {

            //Use this only if the code is for unit tests and other examples to guarantee repeated results
            RandomUtils.useTestSeed();

            //Creating a data model to be passed on to RecommenderEvaluator - evaluate method
            //FileDataModel dataModel = new FileDataModel(new File(recsFile));
            // JDBC data model
            MysqlDataSource mysql_datasource = new MysqlDataSource();
            DataModel dataModel = new MySQLJDBCDataModel(
                    mysql_datasource, "assessment_new", "user_id",
                    "item_id", "preference", "timestamp");

            /*Creating an RecommenderEvaluator to get the evaluation done
             you can use AverageAbsoluteDifferenceRecommenderEvaluator() as well*/
            RecommenderEvaluator evaluator = new RMSRecommenderEvaluator();

            //for obtaining User Similarity Evaluation Score
            double userSimEvaluationScore = evaluator.evaluate(userSimRecBuilder, null, dataModel, 0.7, 1.0);
            System.out.println("User Similarity Evaluation score : " + userSimEvaluationScore);

        } catch (TasteException e) {
        }
    }
}
