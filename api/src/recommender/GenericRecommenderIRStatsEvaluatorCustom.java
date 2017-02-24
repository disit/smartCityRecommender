/**
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements. See the NOTICE file distributed with this
 * work for additional information regarding copyright ownership. The ASF
 * licenses this file to You under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */
package recommender;

import java.util.List;
import java.util.Random;

import org.apache.mahout.cf.taste.common.NoSuchUserException;
import org.apache.mahout.cf.taste.common.TasteException;
import org.apache.mahout.cf.taste.eval.DataModelBuilder;
import org.apache.mahout.cf.taste.eval.IRStatistics;
import org.apache.mahout.cf.taste.eval.RecommenderBuilder;
import org.apache.mahout.cf.taste.eval.RecommenderIRStatsEvaluator;
import org.apache.mahout.cf.taste.eval.RelevantItemsDataSplitter;
import org.apache.mahout.cf.taste.impl.common.FastByIDMap;
import org.apache.mahout.cf.taste.impl.common.FastIDSet;
import org.apache.mahout.cf.taste.impl.common.FullRunningAverage;
import org.apache.mahout.cf.taste.impl.common.FullRunningAverageAndStdDev;
import org.apache.mahout.cf.taste.impl.common.LongPrimitiveIterator;
import org.apache.mahout.cf.taste.impl.common.RunningAverage;
import org.apache.mahout.cf.taste.impl.common.RunningAverageAndStdDev;
import org.apache.mahout.cf.taste.impl.model.GenericDataModel;
import org.apache.mahout.cf.taste.model.DataModel;
import org.apache.mahout.cf.taste.model.PreferenceArray;
import org.apache.mahout.cf.taste.recommender.IDRescorer;
import org.apache.mahout.cf.taste.recommender.RecommendedItem;
import org.apache.mahout.cf.taste.recommender.Recommender;
import org.apache.mahout.common.RandomUtils;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import com.google.common.base.Preconditions;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.PrintWriter;
import java.io.UnsupportedEncodingException;
import java.net.URLEncoder;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Properties;
import java.util.logging.Level;
import org.apache.mahout.cf.taste.impl.eval.GenericRelevantItemsDataSplitter;
import org.json.simple.JSONArray;
import org.json.simple.JSONObject;
import static recommender.Recommender.getConnection;
import static recommender.Recommender.getJSON;

/**
 * <p>
 * For each user, these implementation determine the top {@code n} preferences,
 * then evaluate the IR statistics based on a {@link DataModel} that does not
 * have these values. This number {@code n} is the "at" value, as in "precision
 * at 5". For example, this would mean precision evaluated by removing the top 5
 * preferences for a user and then finding the percentage of those 5 items
 * included in the top 5 recommendations for that user.
 * </p>
 */
public final class GenericRecommenderIRStatsEvaluatorCustom implements RecommenderIRStatsEvaluator {

    private static final Logger log = LoggerFactory.getLogger(GenericRecommenderIRStatsEvaluatorCustom.class);

    private static final double LOG2 = Math.log(2.0);

    /**
     * Pass as "relevanceThreshold" argument to
     * {@link #evaluate(RecommenderBuilder, DataModelBuilder, DataModel, IDRescorer, int, double, double)}
     * to have it attempt to compute a reasonable threshold. Note that this will
     * impact performance.
     */
    public static final double CHOOSE_THRESHOLD = Double.NaN;

    private final Random random;
    private final RelevantItemsDataSplitter dataSplitter;

    private final ArrayList<String> list = topUsers();

    private static Properties prop;
    private static HashMap<String, String> settings;

    private static ArrayList<String> users = getUsers();
    private static HashMap user_preferences = getUserPreferences();
    private static HashMap avg_preferences = getAveragePreferences();

    public GenericRecommenderIRStatsEvaluatorCustom() {
        this(new GenericRelevantItemsDataSplitter());
    }

    public GenericRecommenderIRStatsEvaluatorCustom(RelevantItemsDataSplitter dataSplitter) {
        Preconditions.checkNotNull(dataSplitter);
        random = RandomUtils.getRandom();
        this.dataSplitter = dataSplitter;
    }

    @Override
    public IRStatistics evaluate(RecommenderBuilder recommenderBuilder,
            DataModelBuilder dataModelBuilder,
            DataModel dataModel,
            IDRescorer rescorer,
            int at,
            double relevanceThreshold,
            double evaluationPercentage) throws TasteException {

        prop = new Properties();
        try {
            prop.load(GenericRecommenderIRStatsEvaluatorCustom.class.getResourceAsStream("settings.properties"));
        } catch (IOException ex) {
            java.util.logging.Logger.getLogger(GenericRecommenderIRStatsEvaluatorCustom.class.getName()).log(Level.SEVERE, null, ex);
        }

        // load settings from MySQL database
        loadSettings();

        Preconditions.checkArgument(recommenderBuilder != null, "recommenderBuilder is null");
        Preconditions.checkArgument(dataModel != null, "dataModel is null");
        Preconditions.checkArgument(at >= 1, "at must be at least 1");
        Preconditions.checkArgument(evaluationPercentage > 0.0 && evaluationPercentage <= 1.0,
                "Invalid evaluationPercentage: " + evaluationPercentage + ". Must be: 0.0 < evaluationPercentage <= 1.0");

        int numItems = dataModel.getNumItems();
        System.out.println("Data model numItems: " + numItems);
        RunningAverage precision = new FullRunningAverage();
        RunningAverage recall = new FullRunningAverage();
        RunningAverage fallOut = new FullRunningAverage();
        RunningAverage nDCG = new FullRunningAverage();
        int numUsersRecommendedFor = 0;
        int numUsersWithRecommendations = 0;

        // map to store diversity ranges => number of users
        HashMap<String, String> map = new HashMap<>();

        LongPrimitiveIterator it = dataModel.getUserIDs();
        while (it.hasNext()) {

            long userID = it.nextLong();

            if (userID == 0) {
                continue;
            }

            // get the top users
            if (!list.contains(userID + "")) {
                continue;
            }

            if (random.nextDouble() >= evaluationPercentage) {
                // Skipped
                continue;
            }

            long start = System.currentTimeMillis();

            PreferenceArray prefs = dataModel.getPreferencesFromUser(userID);
            System.out.println("User preferences: " + prefs);

            // List some most-preferred items that would count as (most) "relevant" results
            double theRelevanceThreshold = 0;//Double.isNaN(relevanceThreshold) ? computeThreshold(prefs) : relevanceThreshold;
            FastIDSet relevantItemIDs = dataSplitter.getRelevantItemsIDs(userID, at, theRelevanceThreshold, dataModel);
            System.out.println("Relevant items: " + relevantItemIDs);
            System.out.println("Relevance threshold: " + theRelevanceThreshold);

            int numRelevantItems = relevantItemIDs.size();
            if (numRelevantItems <= 0) {
                continue;
            }

            FastByIDMap<PreferenceArray> trainingUsers = new FastByIDMap<>(dataModel.getNumUsers());
            LongPrimitiveIterator it2 = dataModel.getUserIDs();
            while (it2.hasNext()) {
                dataSplitter.processOtherUser(userID, relevantItemIDs, trainingUsers, it2.nextLong(), dataModel);
            }

            DataModel trainingModel = dataModelBuilder == null ? new GenericDataModel(trainingUsers)
                    : dataModelBuilder.buildDataModel(trainingUsers);
            try {
                trainingModel.getPreferencesFromUser(userID);
            } catch (NoSuchUserException nsee) {
                continue; // Oops we excluded all prefs for the user -- just move on
            }

            int size = numRelevantItems + trainingModel.getItemIDsFromUser(userID).size();
            if (size < 2 * at) {
                // Really not enough prefs to meaningfully evaluate this user
                System.out.println("Really not enough prefs (" + size + ") to meaningfully evaluate user: " + userID);
                continue;
            }

            Recommender recommender = recommenderBuilder.buildRecommender(trainingModel);

            int intersectionSize = 0;
            List<RecommendedItem> recommendedItems = recommender.recommend(userID, at, rescorer);
            HashMap<Long, Double> user_preferences = getUserPreferencesList(userID);
            for (RecommendedItem recommendedItem : recommendedItems) {
                double preference = isRelevant(user_preferences, recommendedItem);
                System.out.println("Preference: " + preference);
                if (relevantItemIDs.contains(recommendedItem.getItemID()) || preference != 0) {
                    intersectionSize++;
                }
            }

            int numRecommendedItems = recommendedItems.size();

            // Precision
            if (numRecommendedItems > 0) {
                precision.addDatum((double) intersectionSize / (double) numRecommendedItems);
                System.out.println("intersectionSize: " + intersectionSize + " numRecommendedItems: " + numRecommendedItems);
            }

            // Recall
            recall.addDatum((double) intersectionSize / (double) numRelevantItems);

            // Fall-out
            if (numRelevantItems < size) {
                fallOut.addDatum((double) (numRecommendedItems - intersectionSize)
                        / (double) (numItems - numRelevantItems));
            }

            // nDCG
            // In computing, assume relevant IDs have relevance 1 and others 0
            double cumulativeGain = 0.0;
            double idealizedGain = 0.0;
            for (int i = 0; i < numRecommendedItems; i++) {
                RecommendedItem item = recommendedItems.get(i);
                double discount = 1.0 / log2(i + 2.0); // Classical formulation says log(i+1), but i is 0-based here
                if (relevantItemIDs.contains(item.getItemID())) {
                    cumulativeGain += discount;
                }
                // otherwise we're multiplying discount by relevance 0 so it doesn't do anything

                // Ideally results would be ordered with all relevant ones first, so this theoretical
                // ideal list starts with number of relevant items equal to the total number of relevant items
                if (i < numRelevantItems) {
                    idealizedGain += discount;
                }
            }
            if (idealizedGain > 0.0) {
                nDCG.addDatum(cumulativeGain / idealizedGain);
            }

            // Reach
            numUsersRecommendedFor++;
            if (numRecommendedItems > 0) {
                numUsersWithRecommendations++;
            }

            long end = System.currentTimeMillis();

            log.info("Evaluated with user {} in {}ms", userID, end - start);
            log.info("Precision/recall/fall-out/nDCG/reach: {} / {} / {} / {} / {}",
                    precision.getAverage(), recall.getAverage(), fallOut.getAverage(), nDCG.getAverage(),
                    (double) numUsersWithRecommendations / (double) numUsersRecommendedFor);
            System.out.println("Relevant items: " + numRelevantItems);
            System.out.println("Precision: " + precision.getAverage());
            System.out.println("Recall: " + recall.getAverage());
            System.out.println("Fall-out: " + fallOut.getAverage());
            System.out.println("nDCG: " + nDCG.getAverage());
            System.out.println("Reach: " + (double) numUsersWithRecommendations / (double) numUsersRecommendedFor);
            double diversity = getDiversity(recommendedItems);
            System.out.println("Diversity: " + diversity);
            if (diversity >= 0 && diversity < 0.1) {
                int count = map.get("0-0.1") != null ? Integer.parseInt(map.get("0-0.1")) + 1 : 1;
                map.put("0-0.1", count + "");
            } else if (diversity >= 0.1 && diversity < 0.2) {
                int count = map.get("0.1-0.2") != null ? Integer.parseInt(map.get("0.1-0.2")) + 1 : 1;
                map.put("0.1-0.2", count + "");
            } else if (diversity >= 0.2 && diversity < 0.3) {
                int count = map.get("0.2-0.3") != null ? Integer.parseInt(map.get("0.2-0.3")) + 1 : 1;
                map.put("0.2-0.3", count + "");
            } else if (diversity >= 0.3 && diversity < 0.4) {
                int count = map.get("0.3-0.4") != null ? Integer.parseInt(map.get("0.3-0.4")) + 1 : 1;
                map.put("0.3-0.4", count + "");
            } else if (diversity >= 0.4 && diversity < 0.5) {
                int count = map.get("0.4-0.5") != null ? Integer.parseInt(map.get("0.4-0.5")) + 1 : 1;
                map.put("0.4-0.5", count + "");
            } else if (diversity >= 0.5 && diversity < 0.6) {
                int count = map.get("0.5-0.6") != null ? Integer.parseInt(map.get("0.5-0.6")) + 1 : 1;
                map.put("0.5-0.6", count + "");
            } else if (diversity >= 0.6 && diversity < 0.7) {
                int count = map.get("0.6-0.7") != null ? Integer.parseInt(map.get("0.6-0.7")) + 1 : 1;
                map.put("0.6-0.7", count + "");
            } else if (diversity >= 0.7 && diversity < 0.8) {
                int count = map.get("0.7-0.8") != null ? Integer.parseInt(map.get("0.7-0.8")) + 1 : 1;
                map.put("0.7-0.8", count + "");
            } else if (diversity >= 0.8 && diversity < 0.9) {
                int count = map.get("0.8-0.9") != null ? Integer.parseInt(map.get("0.8-0.9")) + 1 : 1;
                map.put("0.8-0.9", count + "");
            } else if (diversity >= 0.9) {
                int count = map.get("0.9-1") != null ? Integer.parseInt(map.get("0.9-1")) + 1 : 1;
                map.put("0.9-1", count + "");
            }
        }

        JSONObject json = new JSONObject(map);

        writeFile(prop.getProperty("metrics_file"), json.toJSONString());

        return new IRStatisticsImplCustom(
                precision.getAverage(),
                recall.getAverage(),
                fallOut.getAverage(),
                nDCG.getAverage(),
                (double) numUsersWithRecommendations / (double) numUsersRecommendedFor);
    }

    // get the macroclass of a serviceURI
    public static String getMacroclass(String serviceURI) {
        Connection conn = null;
        String macroclass = "";
        try {
            String query = "SELECT DISTINCT ?s ?type WHERE {\n"
                    + "BIND(<" + serviceURI + "> AS ?s)\n"
                    + "?s a ?t.\n"
                    + "FILTER(?t!=km4c:RegularService && ?t!=km4c:Service && ?t!=km4c:DigitalLocation && ?t!=km4c:TransverseService)\n"
                    + "?t rdfs:subClassOf ?type\n"
                    + "FILTER (STRSTARTS(str(?type), \"http://www.disit.org/km4city/schema#\"))\n"
                    + "}";
            // perform SPARQL query
            String url = settings.get("rdf_url") + "?query=" + URLEncoder.encode(query, "UTF-8") + "&format=json";
            JSONObject results = getJSON(url);
            results = (JSONObject) results.get("results");
            JSONArray list = (JSONArray) results.get("bindings");
            conn = getConnection();
            for (Object obj : list) {
                JSONObject o = (JSONObject) obj;
                macroclass = (String) ((JSONObject) o.get("type")).get("value");
            }
        } catch (UnsupportedEncodingException | NumberFormatException ex) {
            java.util.logging.Logger.getLogger(recommender.Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                java.util.logging.Logger.getLogger(recommender.Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return macroclass;
    }

    // get the serviceURI from MySQL database (insert the item if not present)
    public static String getServiceURI(long itemID) {
        Connection conn = null;
        String serviceURI = null;
        try {
            conn = getConnection();

            PreparedStatement preparedStatement = conn.prepareStatement("SELECT item FROM recommender.assessment_items WHERE id = ?");
            preparedStatement.setLong(1, itemID);
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                serviceURI = rs.getString("item");
            }
        } catch (SQLException ex) {
            java.util.logging.Logger.getLogger(recommender.Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                java.util.logging.Logger.getLogger(recommender.Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return serviceURI;
    }

    // check if an item has the same macroclass as any of the relevant items, in case yes then return the correspondent average preference
    public static double isRelevant(HashMap<Long, Double> user_preferences, RecommendedItem recommendedItem) {
        double sum = 0;
        int counter = 0;
        String recommendedItemServiceURI = getServiceURI(recommendedItem.getItemID());
        Iterator it = user_preferences.keySet().iterator();
        if (recommendedItemServiceURI != null) {
            while (it.hasNext()) {
                long itemID = (long) it.next();
                String serviceURI = getServiceURI(itemID);
                if (serviceURI != null && getMacroclass(serviceURI).equals(getMacroclass(recommendedItemServiceURI))) {
                    sum += (double) user_preferences.get(itemID);
                    counter++;
                }
            }
        }
        if (counter != 0) {
            System.out.println("Relevance: " + (sum / counter));
            return sum / counter;
        } else {
            System.out.println("Relevance: 0");
            return 0;
        }
    }

    // load settings from MySQL database
    public static void loadSettings() {
        Connection conn = getConnection();
        if (settings == null) {
            settings = new HashMap<>();
        }
        try {
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT * FROM recommender.settings");
            preparedStatement.executeQuery();
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                settings.put(rs.getString("name"), rs.getString("value"));
            }
        } catch (SQLException ex) {
            java.util.logging.Logger.getLogger(recommender.Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    java.util.logging.Logger.getLogger(recommender.Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
    }

    public static void writeFile(String file, String log) {
        PrintWriter writer = null;
        try {
            writer = new PrintWriter(new FileOutputStream(new File(file), true));
            writer.println(log);
            writer.close();
        } catch (FileNotFoundException ex) {
            java.util.logging.Logger.getLogger(GenericRecommenderIRStatsEvaluatorCustom.class.getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (writer != null) {
                writer.close();
            }
        }
    }

    private static double computeThreshold(PreferenceArray prefs) {
        if (prefs.length() < 2) {
            // Not enough data points -- return a threshold that allows everything
            return Double.NEGATIVE_INFINITY;
        }
        RunningAverageAndStdDev stdDev = new FullRunningAverageAndStdDev();
        int size = prefs.length();
        for (int i = 0; i < size; i++) {
            stdDev.addDatum(prefs.getValue(i));
        }
        return stdDev.getAverage() + stdDev.getStandardDeviation();
    }

    private static double log2(double value) {
        return Math.log(value) / LOG2;
    }

    // temp function to populate assessment table from the old one
    public static ArrayList<String> topUsers() {
        Connection conn = null;
        ArrayList<String> list = new ArrayList<>();
        try {
            conn = getConnection();
            PreparedStatement preparedStatement1 = conn.prepareStatement("SELECT user_id, count(*) as num FROM recommender.assessment_new group by user_id order by num desc LIMIT 10");
            ResultSet rs = preparedStatement1.executeQuery();
            while (rs.next()) {
                list.add(rs.getString("user_id"));
            }
        } catch (SQLException ex) {
            java.util.logging.Logger.getLogger(GenericRecommenderIRStatsEvaluatorCustom.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                java.util.logging.Logger.getLogger(GenericRecommenderIRStatsEvaluatorCustom.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return list;
    }

    // cosine similarity, eq. 3.1.2
    // http://files.grouplens.org/papers/www10_sarwar.pdf    
    public static double getSimilarity(long item1, long item2) {
        double num = 0;
        double denum1 = 0;
        double denum2 = 0;
        for (String user : users) {
            Object user_preference_i = user_preferences.get(user + "|" + item1);
            Object user_preference_j = user_preferences.get(user + "|" + item2);
            Object avg_preference_i = avg_preferences.get(item1);
            Object avg_preference_j = avg_preferences.get(item2);
            if (user_preference_i != null && user_preference_j != null && avg_preference_i != null && avg_preference_j != null) {
                num += ((double) user_preference_i - (double) avg_preference_i) * ((double) user_preference_j - (double) avg_preference_j);
                denum1 += Math.pow(((double) user_preference_i - (double) avg_preference_i), 2);
                denum2 += Math.pow(((double) user_preference_j - (double) avg_preference_j), 2);
            }
        }
        denum1 = Math.sqrt(denum1);
        denum2 = Math.sqrt(denum2);
        if (denum1 != 0 && denum2 != 0) {
            return num / (denum1 * denum2);
        } else {
            return 0;
        }
    }

    public static double getDiversity(List<RecommendedItem> recommendedItems) {
        double diversity = 0;
        for (RecommendedItem i : recommendedItems) {
            for (RecommendedItem j : recommendedItems) {
                if (i.getItemID() != j.getItemID()) {
                    diversity += 1 - getSimilarity(i.getItemID(), j.getItemID());
                }
            }
        }
        if (recommendedItems.size() * (recommendedItems.size() - 1) > 0) {
            return diversity / (recommendedItems.size() * (recommendedItems.size() - 1));
        } else {
            return 0;
        }
    }

    public static double getNovelty(List<RecommendedItem> recommendedItems, long item_id) {
        double novelty = 0;
        for (RecommendedItem i : recommendedItems) {
            if (i.getItemID() != item_id) {
                novelty += 1 - getSimilarity(item_id, i.getItemID());
            }
        }
        return novelty / (recommendedItems.size() - 1);
    }

    public static HashMap<Long, Double> getAveragePreferences() {
        HashMap map = new HashMap<String, Double>();
        Connection conn = null;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT item_id, AVG(preference) AS avg_preference FROM recommender.assessment_new GROUP BY item_id");
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                map.put(rs.getLong("item_id"), rs.getDouble("avg_preference"));
            }
        } catch (SQLException ex) {
            java.util.logging.Logger.getLogger(GenericRecommenderIRStatsEvaluatorCustom.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                java.util.logging.Logger.getLogger(recommender.Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return map;
    }

    public static HashMap<String, Double> getUserPreferences() {
        HashMap map = new HashMap<String, Double>();
        Connection conn = null;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT user_id, item_id, preference FROM recommender.assessment_new WHERE user_id != 0");
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                map.put(rs.getString("user_id") + "|" + rs.getString("item_id"), rs.getDouble("preference"));
            }
        } catch (SQLException ex) {
            java.util.logging.Logger.getLogger(GenericRecommenderIRStatsEvaluatorCustom.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                java.util.logging.Logger.getLogger(GenericRecommenderIRStatsEvaluatorCustom.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return map;
    }

    public static HashMap<Long, Double> getUserPreferencesList(long user_id) {
        HashMap map = new HashMap<String, Double>();
        Connection conn = null;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT item_id, preference FROM recommender.assessment_new WHERE user_id = ?");
            preparedStatement.setLong(1, user_id);
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                map.put(rs.getLong("item_id"), rs.getDouble("preference"));
            }
        } catch (SQLException ex) {
            java.util.logging.Logger.getLogger(GenericRecommenderIRStatsEvaluatorCustom.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                java.util.logging.Logger.getLogger(GenericRecommenderIRStatsEvaluatorCustom.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return map;
    }

    public static ArrayList<String> getUsers() {
        ArrayList<String> users = new ArrayList<>();
        Connection conn = null;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT DISTINCT(user_id) FROM recommender.assessment_new WHERE user_id != 0");
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                users.add(rs.getString("user_id"));
            }
        } catch (SQLException ex) {
            java.util.logging.Logger.getLogger(GenericRecommenderIRStatsEvaluatorCustom.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                java.util.logging.Logger.getLogger(GenericRecommenderIRStatsEvaluatorCustom.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return users;
    }

    public static ArrayList<Long> getItems() {
        ArrayList<Long> items = new ArrayList<>();
        Connection conn = null;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT DISTINCT(item_id) FROM recommender.assessment_new WHERE user_id != 0");
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                items.add(rs.getLong("item_id"));
            }
        } catch (SQLException ex) {
            java.util.logging.Logger.getLogger(GenericRecommenderIRStatsEvaluatorCustom.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                java.util.logging.Logger.getLogger(GenericRecommenderIRStatsEvaluatorCustom.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return items;
    }

    /**
     * Returns {@code true} if the specified number is a Not-a-Number (NaN)
     * value, {@code false} otherwise.
     *
     * @param v the value to be tested.
     * @return {@code true} if the argument is NaN; {@code false} otherwise.
     */
    static public boolean isNaN(double v) {
        return (v != v);
    }
}
