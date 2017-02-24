/*
 * https://mahout.apache.org/users/recommender/userbased-5-minutes.html
 * https://mahout.apache.org/users/recommender/recommender-documentation.html
 * https://mahout.apache.org/users/recommender/matrix-factorization.html
 * http://mahout.apache.org/users/basics/algorithms.html
 * https://builds.apache.org/job/Mahout-Quality/javadoc/
 * https://github.com/apache/mahout/blob/master/integration/src/main/java/org/apache/mahout/cf/taste/impl/model/jdbc/PostgreSQLJDBCDataModel.java
 * http://www.javacodegeeks.com/2013/10/creating-an-on-line-recommender-system-with-apache-mahout.html
 * http://grouplens.org/datasets/movielens/
 * https://github.com/cloudera/oryx derivato da Mahout
 * https://bigml.com/
 * https://github.com/pferrel/solr-recommender
 * https://en.wikipedia.org/wiki/Singular_value_decomposition
 * http://stackoverflow.com/questions/14668561/apache-mahout-should-i-use-it-to-build-a-custom-recommender
 * http://stackoverflow.com/questions/8773861/candidate-strategy-for-genericuserbasedrecommender-in-mahout
 * http://www.netflixprize.com/
 * http://www.librec.net/
 * http://www.mymedialite.net/
 * http://www.disit.org/drupal/?q=it/node/6598
 * http://192.168.0.8:8080/SmartCityRecommender/?action=recommend&userID=1&latitude=43.7727&longitude=11.2532&distance=1
 * http://www.disit.org/drupal/?q=it/node/6598
 * http://servicemap.disit.org/WebAppGrafo/api/?serviceUri=http://www.disit.org/km4city/resource/76933db4b76647226ddabbb62477cd12&format=json
 * http://blog.trifork.com/2009/12/09/mahout-taste-part-one-introduction/
 * http://blog.trifork.com/2010/04/15/mahout-taste-part-two-getting-started/
 * http://caucho.com/ <--- Resin (alternative to Tomcat)
 * http://www.joda.org/joda-time/
 * http://sujitpal.blogspot.it/2012/08/learning-mahout-collaborative-filtering.html
 * http://www.warski.org/blog/2013/10/creating-an-on-line-recommender-system-with-apache-mahout/
 *
 * regular expressions
 * http://regexr.com/
 *
 * FILTER(?sd >= xsd:dateTime("2015-09-21T16:31:00"))
 * To clean up the recommender truncate the MySQL tables: dislike, preferences, recommendations, recommendations_log, users, general_stats, users_stats
 *
 * Validate JSON
 * http://jsonlint.com/
 *
 * To create a path get directions using Google Earth and then export it from History with right click to KLM
 *
 * Here's an online tool that you can select your KML and export into text, CSV, etc.
 * http://www.zonums.com/online/kml2x.php
 *
 * Here's one to convert to CSV for Excel, etc.
 * http://garmin.gps-data-team.com/poi_manager.php
 *
 * Lots of KML tools can be found here:
 * http://www.zonums.com/index.html
 * 
 * create a federated table (put the word "federated" in the [mysqld] section of my.cnf)
 *
 * CREATE TABLE recommender.`AccessLog` (
 * `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 * `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 * `mode` varchar(45) DEFAULT NULL,
 * `ip` varchar(45) DEFAULT NULL,
 * `userAgent` varchar(255) DEFAULT NULL,
 * `uid` varchar(255) DEFAULT NULL,
 * `serviceUri` varchar(255) DEFAULT NULL,
 * `selection` varchar(255) DEFAULT NULL,
 * `categories` text,
 * `maxResults` varchar(255) DEFAULT NULL,
 * `maxDistance` varchar(255) DEFAULT NULL,
 * `text` varchar(255) DEFAULT NULL,
 * `queryId` varchar(45) DEFAULT NULL,
 * `format` varchar(45) DEFAULT NULL,
 * `email` varchar(45) DEFAULT NULL,
 * PRIMARY KEY (`id`),
 * KEY `idx_AccessLog_timestamp` (`timestamp`),
 * KEY `idx_AccessLog_mode` (`mode`),
 * KEY `idx_AccessLog_ip` (`ip`),
 * KEY `idx_AccessLog_uid` (`uid`)
 * ) ENGINE=FEDERATED
 * DEFAULT CHARSET=latin1
 * CONNECTION='mysql://root:ubuntu@192.168.0.20:3306/ServiceMap/AccessLog';
 *
 * load preferences into MySQL table with
 * LOAD DATA LOCAL INFILE 'C:\\Users\\cenni\\Downloads\\ml-20m\\ratings.csv' INTO TABLE recommender.preferences FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';
 * LOAD DATA LOCAL INFILE 'C:\\Users\\cenni\\Downloads\\ml-20m\\ratings.csv' INTO TABLE test.dummy FIELDS TERMINATED BY ',' ENCLOSED BY '"' LINES TERMINATED BY '\n';
 */
package recommender;

import com.mysql.jdbc.jdbc2.optional.MysqlDataSource;
import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.io.UnsupportedEncodingException;
import java.net.MalformedURLException;
import java.net.URL;
import java.net.URLConnection;
import java.net.URLEncoder;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.ResultSetMetaData;
import java.sql.SQLException;
import java.text.Format;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Calendar;
import java.util.Collections;
import java.util.Comparator;
import java.util.Date;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Properties;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;
import java.util.logging.Level;
import java.util.logging.Logger;
import javax.sql.DataSource;
import org.apache.commons.collections.BidiMap;
import org.apache.commons.collections.bidimap.TreeBidiMap;
import org.apache.mahout.cf.taste.common.TasteException;
import org.apache.mahout.cf.taste.eval.IRStatistics;
import org.apache.mahout.cf.taste.eval.RecommenderBuilder;
import org.apache.mahout.cf.taste.eval.RecommenderEvaluator;
import org.apache.mahout.cf.taste.eval.RecommenderIRStatsEvaluator;
import org.apache.mahout.cf.taste.impl.common.FastIDSet;
import org.apache.mahout.cf.taste.impl.eval.AverageAbsoluteDifferenceRecommenderEvaluator;
import org.apache.mahout.cf.taste.impl.eval.GenericRecommenderIRStatsEvaluator;
import org.apache.mahout.cf.taste.impl.model.file.FileDataModel;
import org.apache.mahout.cf.taste.impl.model.jdbc.MySQLJDBCDataModel;
import org.apache.mahout.cf.taste.impl.model.jdbc.ReloadFromJDBCDataModel;
import org.apache.mahout.cf.taste.impl.neighborhood.ThresholdUserNeighborhood;
import org.apache.mahout.cf.taste.impl.recommender.GenericUserBasedRecommender;
import org.apache.mahout.cf.taste.impl.recommender.svd.ALSWRFactorizer;
import org.apache.mahout.cf.taste.impl.recommender.svd.Factorizer;
import org.apache.mahout.cf.taste.impl.recommender.svd.SVDRecommender;
import org.apache.mahout.cf.taste.impl.similarity.PearsonCorrelationSimilarity;
import org.apache.mahout.cf.taste.model.DataModel;
import org.apache.mahout.cf.taste.model.JDBCDataModel;
import org.apache.mahout.cf.taste.model.PreferenceArray;
import org.apache.mahout.cf.taste.neighborhood.UserNeighborhood;
import org.apache.mahout.cf.taste.recommender.RecommendedItem;
import org.apache.mahout.cf.taste.recommender.UserBasedRecommender;
import org.apache.mahout.cf.taste.similarity.UserSimilarity;
import org.apache.mahout.common.RandomUtils;
import org.apache.wink.json4j.JSONException;
import org.apache.wink.json4j.OrderedJSONObject;
import org.json.simple.JSONArray;
import org.json.simple.JSONObject;
import org.json.simple.parser.JSONParser;
import org.json.simple.parser.ParseException;

/**
 *
 * @author Daniele Cenni, daniele.cenni@unifi.it
 */
public class Recommender {

    private static Properties prop;
    private static ConnectionPool connectionPool;
    private static DataSource dataSource;
    private static ConnectionPool connectionPool1;
    private static DataSource dataSource1;
    private static MysqlDataSource mysql_datasource;
    private static JDBCDataModel dm;
    private static ReloadFromJDBCDataModel rdm;
    // bidirectional map to map item_id (long) - service_id (UUID) http://commons.apache.org/proper/commons-collections/userguide.html
    //private static BidiMap services; // services bidirectional map (id <=> service)
    private static BidiMap categories_ids; // categories bidirectional map (id <=> category)
    private static HashMap<String, HashMap<String, String>> categories; // macroclasses and subclasses map (category=>(type => [subclass, macroclass), group => [Health etc.])
    private static HashMap<String, ArrayList<String>> users_profiles; // users profiles map (user => ArrayList of user related subclasses)
    private static HashMap<String, HashMap<String, String>> macroclass_hours;
    private static HashMap<String, String> settings;
    private static HashMap<String, HashMap<String, Integer>> profile_settings;
    private static HashMap<Integer, String> groups_all;
    private static HashMap<Integer, String> groups_student;
    private static HashMap<Integer, String> groups_commuter;
    private static HashMap<Integer, String> groups_citizen;
    private static HashMap<Integer, String> groups_tourist;
    private static HashMap<Integer, String> groups_disabled;
    private static HashMap<Integer, String> groups_operator;
    private static HashMap<String, HashMap<String, String>> groups_langs;
    private static SVDRecommender svdRecommender;
    private static Factorizer factorizer;

    // logger
    private static ScheduledExecutorService scheduledThreadPool;

    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) {
        /*init();
         try {
         Thread.sleep(60000);
         } catch (InterruptedException ex) {
         Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
         }
         System.exit(1);*/
        //String json = "{\"Weather\":{\"head\":{\"location\":\"FIRENZE\",\"vars\":[\"day\",\"description\",\"minTemp\",\"maxTemp\",\"instantDateTime\"]},\"results\":{\"bindings\":[{\"maxTemp\":{\"type\":\"literal\",\"value\":\"22\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-10-09T08:49:00+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Venerdi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"16\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"19\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-10-09T08:49:00+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"pioggia debole\"},\"day\":{\"type\":\"literal\",\"value\":\"Sabato\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"14\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"22\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-10-09T08:49:00+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Domenica\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"10\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-10-09T08:49:00+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Lunedi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-10-09T08:49:00+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"pioggia moderata-forte\"},\"day\":{\"type\":\"literal\",\"value\":\"Martedi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"\"}}]}},\"Bus\":[{\"realtime\":{},\"busLines\":{\"head\":{\"busStop\":\"GIOVANNI DEI MARIGNOLLI\",\"vars\":\"busLine\"},\"results\":{\"bindings\":[{\"busLine\":{\"type\":\"literal\",\"value\":\"23\"}}]}},\"BusStop\":{\"features\":[{\"geometry\":{\"coordinates\":[11.2369,43.7916],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"TransferServiceAndRenting_BusStop\",\"address\":\"VIA DEL PONTE DI MEZZO\",\"serviceUri\":\"http:\\/\\/www.disit.org\\/km4city\\/resource\\/FM0793\",\"name\":\"GIOVANNI DEI MARIGNOLLI\",\"typeLabel\":\"BusStop\"}}],\"type\":\"FeatureCollection\"}},{\"realtime\":{},\"busLines\":{\"head\":{\"busStop\":\"MAGELLANO PANCIATICHI\",\"vars\":\"busLine\"},\"results\":{\"bindings\":[{\"busLine\":{\"type\":\"literal\",\"value\":\"23\"}},{\"busLine\":{\"type\":\"literal\",\"value\":\"5\"}}]}},\"BusStop\":{\"features\":[{\"geometry\":{\"coordinates\":[11.233,43.8002],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"TransferServiceAndRenting_BusStop\",\"address\":\"VIA MAGELLANO\",\"serviceUri\":\"http:\\/\\/www.disit.org\\/km4city\\/resource\\/FM0292\",\"name\":\"MAGELLANO PANCIATICHI\",\"typeLabel\":\"BusStop\"}}],\"type\":\"FeatureCollection\"}},{\"realtime\":{},\"busLines\":{\"head\":{\"busStop\":\"TERZOLLE\",\"vars\":\"busLine\"},\"results\":{\"bindings\":[{\"busLine\":{\"type\":\"literal\",\"value\":\"23\"}},{\"busLine\":{\"type\":\"literal\",\"value\":\"60\"}}]}},\"BusStop\":{\"features\":[{\"geometry\":{\"coordinates\":[11.2339,43.7944],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"TransferServiceAndRenting_BusStop\",\"address\":\"VIA CARLO DEL PRETE\",\"serviceUri\":\"http:\\/\\/www.disit.org\\/km4city\\/resource\\/FM0785\",\"name\":\"TERZOLLE\",\"typeLabel\":\"BusStop\"}}],\"type\":\"FeatureCollection\"}}],\"Transfer Services\":[{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.2394,43.7987],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"TransferServiceAndRenting_Bus_tickets_retail\",\"note\":\"\",\"website\":\"\",\"address\":\"VIA REGINALDO GIULIANI\",\"city\":\"FIRENZE\",\"serviceUri\":\"http:\\/\\/www.disit.org\\/km4city\\/resource\\/rivendita3149\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[\"http:\\/\\/it.dbpedia.org\\/resource\\/Reginaldo_Giuliani\"],\"civic\":\"85\",\"multimedia\":\"\",\"cap\":\"50100\",\"province\":\"FI\",\"phone\":\"\",\"name\":\"Rivendita TABACCHI BRASCHI S.\",\"typeLabel\":\"Bus tickets retail\",\"fax\":\"\",\"email\":\"\"}}],\"type\":\"FeatureCollection\"}},{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.2394,43.7987],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"TransferServiceAndRenting_Bus_tickets_retail\",\"note\":\"\",\"website\":\"\",\"address\":\"VIA REGINALDO GIULIANI\",\"city\":\"FIRENZE\",\"serviceUri\":\"http:\\/\\/www.disit.org\\/km4city\\/resource\\/rivendita3152\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[\"http:\\/\\/it.dbpedia.org\\/resource\\/Reginaldo_Giuliani\"],\"civic\":\"52\",\"multimedia\":\"\",\"cap\":\"50100\",\"province\":\"FI\",\"phone\":\"\",\"name\":\"Rivendita TABACCHI DI SOTTOCORNOLA ALESSANDRO\",\"typeLabel\":\"Bus tickets retail\",\"fax\":\"\",\"email\":\"\"}}],\"type\":\"FeatureCollection\"}},{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.2389,43.7995],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"TransferServiceAndRenting_Bus_tickets_retail\",\"note\":\"\",\"website\":\"\",\"address\":\"VIA REGINALDO GIULIANI\",\"city\":\"FIRENZE\",\"serviceUri\":\"http:\\/\\/www.disit.org\\/km4city\\/resource\\/rivendita3025\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[\"http:\\/\\/it.dbpedia.org\\/resource\\/Reginaldo_Giuliani\"],\"civic\":\"101\",\"multimedia\":\"\",\"cap\":\"50100\",\"province\":\"FI\",\"phone\":\"\",\"name\":\"Rivendita LELLO BAR S.A.S.\",\"typeLabel\":\"Bus tickets retail\",\"fax\":\"\",\"email\":\"\"}}],\"type\":\"FeatureCollection\"}}],\"Hotel\":[{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.2348,43.8107],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"Accommodation_Boarding_house\",\"note\":\"\",\"website\":\"\",\"address\":\"VIA DELLE PANCHE\",\"city\":\"FIRENZE\",\"serviceUri\":\"http:\\/\\/www.disit.org\\/km4city\\/resource\\/fb024699c085e9846dcaf48caa386d62\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[],\"civic\":\"209\",\"multimedia\":\"\",\"cap\":\"50141\",\"province\":\"FI\",\"phone\":\"055454823\",\"name\":\"VILLE_MEDICEE_B&B_CASTELLO\",\"typeLabel\":\"Boarding house\",\"fax\":\"\",\"email\":\"emimail230@yahoo.it\"}}],\"type\":\"FeatureCollection\"}},{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.2337,43.7871],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"Accommodation_Boarding_house\",\"note\":\"Servizi: alimenti\",\"website\":\"http:\\/\\/www.ilgrillodifirenze.it\",\"address\":\"VIA MARAGLIANO\",\"city\":\"FIRENZE\",\"serviceUri\":\"http:\\/\\/www.disit.org\\/km4city\\/resource\\/1bfbe2d81edbb2415dfe8bcdc51cda58\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[],\"civic\":\"69\",\"multimedia\":\"\",\"cap\":\"50144\",\"province\":\"FI\",\"phone\":\"0553200905\",\"name\":\"IL GRILLO DI FIRENZE\",\"typeLabel\":\"Boarding house\",\"fax\":\"0553200905\",\"email\":\"info@ilgrillodifirenze.it\"}}],\"type\":\"FeatureCollection\"}},{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.2304,43.8076],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"Accommodation_Boarding_house\",\"note\":\"\",\"website\":\"www.bedandcar.it\",\"address\":\"VIA P. FANFANI\",\"city\":\"FIRENZE\",\"serviceUri\":\"http:\\/\\/www.disit.org\\/km4city\\/resource\\/62903106bc12b15851bb7fde5c8abe60\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[],\"civic\":\"26\",\"multimedia\":\"\",\"cap\":\"50100\",\"province\":\"FI\",\"phone\":\"0558722120\",\"name\":\"EUROTRAVEL_BED_AND_CAR_SRL_(FANFANI)\",\"typeLabel\":\"Boarding house\",\"fax\":\"\",\"email\":\"bedandcar@virgilio.it\"}}],\"type\":\"FeatureCollection\"}}]}";
        //init();
        //calculateRecommendationStats(30);
        //System.exit(1);
        //dislike("41767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519", "Weather");
        //dislike("41767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519", "Services and Utilities");
        //dislike("41767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519", "Hotel");
        //removeDislike("41767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519");
        //recommendPaths();
        //System.exit(1);
        //loadRecommender();
        //System.exit(1);
        //estimateRSSI();
        //System.exit(1);
        //dislike("41767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519", "Weather");
        //removeDislike("36767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519");
        //dislikeSubclass("36767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519", "Bike_rack");
        /*init();
         JSONObject json = getTweets("all", "Twitter1", 1);
         System.out.println(json.toJSONString());
         System.exit(1);*/
        /*RecommenderLoggerStatus logger = new RecommenderLoggerStatus();
         logger.run();
         System.exit(1);*/
        //populateAssessment();
        //System.exit(1);
        //init();
        //evaluateRecommender();
        //System.exit(1);
        for (int i = 0; i < 1; i++) {
            long time = System.currentTimeMillis();
            //36767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519
            System.out.println(recommend("36767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519", "all", "en", "manual", 43.798673, 11.2535434, 2, "1.1.0", "", "false", "true", "false", "appID", "uid2", null));
            //System.out.println(recommendForGroup("36767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519", "all", "Hotel", "it", "manual", 43.7727, 11.2532, 2, "1.4.0", "", "true", "appID", "uid2", null));
            long tmp = System.currentTimeMillis();
            System.out.println("Elapsed Time: " + ((tmp - time) / 1000.0) + " s");
            time = tmp;
        }
    }

    public static void init() {
        if (svdRecommender == null) {
            try {
                // load properties file, includes database settings (url, username, password) and connection pool maximum number of connections (used by ConnectionPool)
                prop = new Properties();
                prop.load(Recommender.class.getResourceAsStream("settings.properties"));

                // load settings from MySQL database
                loadSettings();

                //start logging this recommender stats to db every n seconds, read from settings (recommenderLoggingPeriod)
                scheduledThreadPool = Executors.newScheduledThreadPool(1);
                RecommenderLoggerStatus logger = new RecommenderLoggerStatus();

                scheduledThreadPool.scheduleAtFixedRate(logger,
                        1, 86400, TimeUnit.SECONDS); // the logging period is 1 day

                // load macro classes and sub classes into bidirectional map
                loadCategories();

                // load users profiles
                loadUsersProfiles();

                // load groups with their priorities
                loadGroups();

                // load groups with their translations
                loadGroupsLangs();

                // JDBC data model
                mysql_datasource = new MysqlDataSource();

                mysql_datasource.setServerName(prop.getProperty("db_hostname"));
                mysql_datasource.setUser(prop.getProperty("db_username"));
                mysql_datasource.setPassword(prop.getProperty("db_password"));
                mysql_datasource.setDatabaseName(
                        "recommender");

                dm = new MySQLJDBCDataModel(
                        mysql_datasource, "preferences", "user_id",
                        "item_id", "preference", "timestamp");

                // Switching to MEMORY mode. Load all data from database into memory first
                // there is no need of a ConnectionPool because this technique uses a memory-based ReloadFromJDBCDataModel wrapper,
                // decreasing the number of connections to 1
                rdm = new ReloadFromJDBCDataModel((JDBCDataModel) dm);

                // Factorize matrix
                // factorizes the rating matrix using "Alternating-Least-Squares with Weighted-λ-Regularization" as described in the paper
                // "Large-scale Collaborative Filtering for the Netflix Prize" http://machinelearning202.pbworks.com/w/file/fetch/60922097/netflix_aaim08%28submitted%29.pdf
                factorizer = new ALSWRFactorizer(rdm, 2, 0.025, 3);

                // Configure SVD algorithm
                svdRecommender = new SVDRecommender(rdm, factorizer);
            } catch (IOException | TasteException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
    }

    // recommend
    public static void recommend1() {
        try {
            // load the data from the file
            DataModel model = new FileDataModel(new File("C:\\Users\\cenni\\Downloads\\dataset.csv"));

            // JDBC data model
            /*MysqlDataSource dataSource = new MysqlDataSource();
             dataSource.setServerName("my_database_host");
             dataSource.setUser("my_user");
             dataSource.setPassword("my_password");
             dataSource.setDatabaseName("my_database_name");

             JDBCDataModel model = new MySQLJDBCDataModel(
             dataSource, "my_prefs_table", "my_user_column",
             "my_item_column", "my_pref_value_column", "my_timestamp_column");*/
            // compute the correlation coefficient between their interactions
            UserSimilarity similarity = new PearsonCorrelationSimilarity(model);
            // define which similar users we want to leverage for the recommender; use all that have a similarity greater than 0.1
            UserNeighborhood neighborhood = new ThresholdUserNeighborhood(0.1, similarity, model);
            // create the recommender
            UserBasedRecommender recommender = new GenericUserBasedRecommender(model, neighborhood, similarity);
            List recommendations = recommender.recommend(2, 3);
            // print the recommendations
            recommendations.stream().forEach((recommendation) -> {
                System.out.println(recommendation);
            });

        } catch (TasteException | IOException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
    }

    // recommend, timestamp must be null in production (used by loadRecommender)
    // if log is true, then log the recommendation to the recommender.recommendations_log MySQL table
    public static String recommend(String user, String profile, String language, String mode, double latitude, double longitude, double distance, String version, String userAgent, String aroundme, String svdEnabled, String alreadyRecommended, String appID, String uid2, String timestamp) {
        HashMap<String, Long> timing = new HashMap<>();
        long time = System.currentTimeMillis();
        // init recommender and load settings (only if recommender is null)
        init();
        long tmp = System.currentTimeMillis();
        timing.put("init", (tmp - time));
        time = tmp;

        // load settings
        loadSettings();
        tmp = System.currentTimeMillis();
        timing.put("loadSettings", (tmp - time));
        time = tmp;

        // load groups with their priorities
        loadGroups();
        tmp = System.currentTimeMillis();
        timing.put("loadGroups", (tmp - time));
        time = tmp;

        // load groups with their translations
        loadGroupsLangs();
        tmp = System.currentTimeMillis();
        timing.put("loadGroupsLangs", (tmp - time));
        time = tmp;

        // set user preferences
        if (aroundme == null) {
            setUserPreferences(user, profile, userAgent);
        }
        tmp = System.currentTimeMillis();
        timing.put("setUserPreferences", (tmp - time));
        time = tmp;

        // refresh the recommender (delete cache)
        if (aroundme == null) {
            svdRecommender.refresh(null);
        }
        tmp = System.currentTimeMillis();
        timing.put("refreshRecommender", (tmp - time));
        time = tmp;

        // get the list of recommendations grouped per group (key = group, value = ArrayList of recommendations)
        // if aroundme = true then svdEnabled = false
        Object[] obj = getRecommendations(user, profile, aroundme != null && aroundme.equalsIgnoreCase("true") ? "false" : svdEnabled, latitude, longitude, distance); // obj[0] = list of recommendations, obj[1] = sparql query, obj[2] = disliked subclasses, obj[3] = disliked groups
        HashMap<String, ArrayList<HashMap<String, String>>> recommendations = obj[0] != null ? (HashMap<String, ArrayList<HashMap<String, String>>>) obj[0] : null;
        // disliked subclasses
        ArrayList<String> dislikedSubclasses = obj[1] != null ? (ArrayList<String>) obj[1] : null;
        // disliked groups
        ArrayList<String> dislikedGroups = obj[2] != null ? (ArrayList<String>) obj[2] : null;
        String sparql = (String) obj[3];
        tmp = System.currentTimeMillis();
        timing.put("getRecommendations", (tmp - time));
        time = tmp;

        // update user profile
        updateUserProfile(user, profile);
        tmp = System.currentTimeMillis();
        timing.put("updateUserProfile", (tmp - time));
        //time = tmp;

        // get the json result
        // aroundme = true has precedence on alreadyRecommended
        return getRecommendationsJSON(recommendations, user, profile, language, mode, latitude, longitude, sparql, distance, timestamp, dislikedSubclasses, dislikedGroups, version, timing, aroundme != null && aroundme.equalsIgnoreCase("true") ? aroundme : alreadyRecommended, appID, uid2, aroundme != null && aroundme.equalsIgnoreCase("true") ? "false" : svdEnabled);
    }

    // recommend for a group, timestamp must be null in production (used by loadRecommender)
    // if log is true, then log the recommendation to the recommender.recommendations_log MySQL table
    public static String recommendForGroup(String user, String profile, String group, String language, String mode, double latitude, double longitude, double distance, String version, String userAgent, String svdEnabled, String appID, String uid2, String timestamp) {
        HashMap<String, Long> timing = new HashMap<>();
        long time = System.currentTimeMillis();
        // init recommender and load settings (only if recommender is null)
        init();
        long tmp = System.currentTimeMillis();
        timing.put("init", (tmp - time));
        time = tmp;

        // load settings
        loadSettings();
        tmp = System.currentTimeMillis();
        timing.put("loadSettings", (tmp - time));
        time = tmp;

        // load groups with their priorities
        loadGroups();
        tmp = System.currentTimeMillis();
        timing.put("loadGroups", (tmp - time));
        time = tmp;

        // load groups with their translations
        loadGroupsLangs();
        tmp = System.currentTimeMillis();
        timing.put("loadGroupsLangs", (tmp - time));
        time = tmp;

        // set user preferences
        // svdEnabled = null = true
        if (svdEnabled == null) {
            setUserPreferences(user, profile, userAgent);
        }
        tmp = System.currentTimeMillis();
        timing.put("setUserPreferences", (tmp - time));
        time = tmp;

        // refresh the recommender (delete cache)
        if (svdEnabled == null) {
            svdRecommender.refresh(null);
        }
        tmp = System.currentTimeMillis();
        timing.put("refreshRecommender", (tmp - time));
        time = tmp;

        // get the list of recommendations grouped per group (key = group, value = ArrayList of recommendations)
        Object[] obj = getRecommendationsForGroup(user, profile, group, svdEnabled, latitude, longitude, distance); // obj[0] = list of recommendations, obj[1] = sparql query
        HashMap<String, ArrayList<HashMap<String, String>>> recommendations = obj[0] != null ? (HashMap<String, ArrayList<HashMap<String, String>>>) obj[0] : null;
        // disliked subclasses
        ArrayList<String> dislikedSubclasses = obj[1] != null ? (ArrayList<String>) obj[1] : null;
        String sparql = (String) obj[2];
        tmp = System.currentTimeMillis();
        timing.put("getRecommendations", (tmp - time));
        time = tmp;

        // update user profile
        updateUserProfile(user, profile);
        tmp = System.currentTimeMillis();
        timing.put("updateUserProfile", (tmp - time));
        //time = tmp;

        // get the json result
        return getRecommendationsJSONForGroup(recommendations, user, profile, group, language, mode, latitude, longitude, sparql, distance, timestamp, dislikedSubclasses, version, appID, uid2, timing, svdEnabled);
    }

    // Singular value decomposition–based recommender
    public static Object[] svdRecommend(String user, String profile, String group, String svdEnabled, double latitude, double longitude, double distance) {
        // init recommender and load settings
        //init();
        // refresh the recommender (delete cache)
        //svdRecommender.refresh(null);
        ArrayList<String> dislikedSubclasses = getDislikedSubclasses(user);
        ArrayList<String> dislikedGroups = getDislikedGroups(user);
        ArrayList<String> recommendations_list = new ArrayList<>(); // array list of results for a group related to the user profile

        // if svd is disabled, then return all macroclass
        if (svdEnabled != null && svdEnabled.equalsIgnoreCase("false")) {
            /*Iterator it = categories.keySet().iterator();
             while (it.hasNext()) {
             String type = (String) it.next();
             HashMap<String, String> tmp = categories.get(type);
             if (tmp.get("type").equals("macroclass")) {
             recommendations_list.add(type);
             }
             }*/
            return new Object[]{null, dislikedSubclasses, dislikedGroups};
        }

        try {
            // Load data from file
            //DataModel dm = new FileDataModel(new File("C:\\Users\\cenni\\Downloads\\dataset.csv"));

            // get user id from user
            long userID = getUserID(user, profile, null);
            List<RecommendedItem> topItems = svdRecommender.recommend(userID, Integer.parseInt(settings.get("max_recommendations_returned_from_recommender")), null, Boolean.parseBoolean(settings.get("include_known_items")));
            for (RecommendedItem recommendation : topItems) {
                //System.out.println(userID + ", " + getService(recommendation.getItemID()) + ", " + recommendation.getValue());
                //recommendations_list.add((String) categories_ids.getKey(recommendation.getItemID()));

                // recommendations (subclasses)
                String subclass = (String) categories_ids.getKey(recommendation.getItemID());

                HashMap<String, String> tmp = categories.get(subclass);
                String g = (String) tmp.get("group");

                // if group is not null, then the user is asking for recommendations only belonging to it, except disliked subclasses (for the time period defined in the setting ignore_dislike_subclass_days)
                if (group != null) {
                    // add the recommendation to the list only if it belongs to the requested group
                    if (g.equalsIgnoreCase(group) && !dislikedSubclasses.contains(subclass) && !recommendations_list.contains(subclass)) {
                        recommendations_list.add(subclass);
                    }
                } // all recommendations, except disliked subclasses (for the time period defined in the setting ignore_dislike_subclass_days) and groups (for the time period defined in the setting ignore_dislike_groups_days)
                else {
                    if (!dislikedSubclasses.contains(subclass) && !dislikedGroups.contains(g) && !recommendations_list.contains(subclass)) {
                        recommendations_list.add((String) categories_ids.getKey(recommendation.getItemID()));

                    }
                }
            }
        } catch (TasteException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
        return new Object[]{recommendations_list, dislikedSubclasses, dislikedGroups};
    }

    // get recommendations (services) and the sparql query used to get them
    // object[0] = recommendations (HashMap<String, ArrayList<String>>), object[1] sparql query
    public static Object[] getRecommendations(String user, String profile, String svdEnabled, double latitude, double longitude, double distance) {
        HashMap<String, ArrayList<HashMap<String, String>>> result = new HashMap<>();
        String sparql = "";
        ArrayList<String> dislikedSubclasses = null;
        ArrayList<String> dislikedGroups = null;
        try {
            // get the list of recommended subclasses
            Object[] recommendations = svdRecommend(user, profile, null, svdEnabled, latitude, longitude, distance);
            ArrayList<String> recommendations_list = (ArrayList<String>) recommendations[0];
            dislikedSubclasses = (ArrayList<String>) recommendations[1];
            dislikedGroups = (ArrayList<String>) recommendations[2];

            if (recommendations_list == null) {
                recommendations_list = new ArrayList<String>();
            }
            // if there are no recommendations add all the macroclasses or subclasses belonging to this profile, if not disliked
            //if (recommendations_list.size() == 0) {
            //if (!profile.equalsIgnoreCase("all")) {
            ArrayList<String> subclasses = users_profiles.get(profile);
            for (String subclass : subclasses) {
                if (!dislikedSubclasses.contains(subclass)) {
                    String t = subclass.equalsIgnoreCase("Bus") ? "BusStop" : subclass;
                    t = subclass.equalsIgnoreCase("Events") ? "Event" : subclass;
                    recommendations_list.add(t);
                }
            }
            /*} else {
             // macroclasses cannot be disliked
             ArrayList<String> macroclasses = getMacroclasses();
             for (String macroclass : macroclasses) {
             String t = macroclass.equalsIgnoreCase("Bus") ? "BusStop" : macroclass;
             t = macroclass.equalsIgnoreCase("Events") ? "Event" : t;
             recommendations_list.add(t);
             }
             }*/
            //}
            // always include Bus, if not disliked
            if (!dislikedSubclasses.contains("Bus")) {
                recommendations_list.add("BusStop");
            }
            // always include Events, if not disliked
            if (!dislikedSubclasses.contains("Events")) {
                recommendations_list.add("Event");
            }

            // perform SPARQL query
            JSONObject results = null;
            if (recommendations_list.size() > 0) {
                sparql = getSPARQLQuery(recommendations_list, latitude, longitude, distance);
                String url = settings.get("rdf_url") + "?query=" + URLEncoder.encode(sparql, "UTF-8") + "&format=json";
                //results = getJSON(url);
                results = postRequest(settings.get("rdf_url"), "query=" + URLEncoder.encode(sparql, "UTF-8") + "&format=json");
            }

            // if results are empty then return
            if (results == null || results.get("results") == null) {
                return new Object[]{null, dislikedSubclasses, dislikedGroups, sparql};
            }

            results = (JSONObject) results.get("results");
            JSONArray list = (JSONArray) results.get("bindings");
            for (Object obj : list) {
                JSONObject o = (JSONObject) obj;
                o = (JSONObject) o.get("s");
                String s = (String) o.get("value"); // serviceUri
                o = (JSONObject) obj;
                o = (JSONObject) o.get("t");
                String t = (String) o.get("value"); // subclass
                o = (JSONObject) obj;
                o = (JSONObject) o.get("dist");
                String dist = (String) o.get("value"); // distance
                String name = "";
                String address = "";
                String civic = "";
                o = (JSONObject) obj;
                if (o.get("name") != null) {
                    o = (JSONObject) o.get("name");
                    name = (String) o.get("value"); // name
                }
                o = (JSONObject) obj;
                if (o.get("address") != null) {
                    o = (JSONObject) o.get("address");
                    address = (String) o.get("value"); // address
                }
                if (o.get("civic") != null) {
                    o = (JSONObject) obj;
                    o = (JSONObject) o.get("civic");
                    civic = (String) o.get("value"); // civic
                }

                // get the subclass' group
                String group = getSubclassGroup(t);

                // put the service in the map
                HashMap<String, String> tmp = new HashMap<>();
                tmp.put("serviceURI", s);
                tmp.put("distance", dist);
                tmp.put("subclass", t);
                tmp.put("name", name);
                tmp.put("address", address);
                tmp.put("civic", civic);
                if (result.get(group) == null) {
                    ArrayList<HashMap<String, String>> services_list = new ArrayList<>();
                    services_list.add(tmp);
                    result.put(group, services_list);
                } else {
                    result.get(group).add(tmp);
                }
            }
        } catch (UnsupportedEncodingException | NumberFormatException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
        return new Object[]{result, dislikedSubclasses, dislikedGroups, sparql};
    }

    // get data for recommendations from ServiceMap
    // this method return a list of recommendations, grouped per groups (reported in the table 'groups' of the MySQL database), as defined for each user profile (e.g. student)
    // the key of the serviceURIsList is the user profile, and the value is an array list of recommendations
    public static String getRecommendationsJSON(HashMap<String, ArrayList<HashMap<String, String>>> serviceURIsList, String user, String profile, String language, String mode, double latitude, double longitude, String sparql, double distance, String timestamp, ArrayList<String> dislikedSubclassesList, ArrayList<String> dislikedGroupsList, String version, HashMap<String, Long> timing, String aroundme, String appID, String uid2, String svdEnabled) {
        // Extension of the basic JSONObject. This class allows control of the serialization order of attributes
        // The order in which items are put into the instance controls the order in which they are serialized out. For example, the last item put is the last item serialized.
        long time = System.currentTimeMillis();
        OrderedJSONObject obj_log = new OrderedJSONObject();
        JSONArray array = new JSONArray();
        Connection conn = null;
        int nrecommendations = 0;
        int nrecommendations_weather = 0;
        try {
            conn = getConnection();
            long[] assessor_userID = getUserIDAssessor(user, profile, null);
            long userID = assessor_userID[1];
            long assessor = assessor_userID[0];
            for (int i = 1; i <= getGroup(profile).size(); i++) {
                int counter = 0;
                boolean empty = true; // flag that indicates if there are suggestions in this group that have to be added to the JSON returned by this function
                JSONObject obj = new JSONObject();
                String group = getGroup(profile).get(i);

                // if recommendations are empty, check if Weather has to be provided and then exit from the for cycle
                if (serviceURIsList == null) {
                    if (!dislikedGroupsList.contains("Weather")) {
                        // insert weather, this is not part of groups and it is always provided
                        JSONObject weather = getWeather(latitude, longitude);
                        if (weather != null) {
                            obj.put("suggestions", weather);
                            obj_log.put("Weather", weather); // json to be logged to MySQL table
                            empty = false; // means that this suggestions have to be added to the JSON returned by this function
                            // increment the provided recommendation
                            if (weather.get("ERROR") == null && weather.size() > 0) {
                                nrecommendations_weather++;
                            }
                        }
                    }
                    if (!dislikedGroupsList.contains("Twitter1") && version != null) {
                        JSONObject tweets = getTweets(user, profile, "Twitter1", assessor);
                        //obj_log.put("Weather", tweets); // json to be logged to MySQL table
                        array.add(tweets);
                    }
                    if (!dislikedGroupsList.contains("Twitter2") && version != null) {
                        JSONObject tweets = getTweets(user, profile, "Twitter2", assessor);
                        //obj_log.put("Weather", tweets); // json to be logged to MySQL table
                        array.add(tweets);
                    }
                    if (!dislikedGroupsList.contains("Twitter3") && version != null) {
                        JSONObject tweets = getTweets(user, profile, "Twitter3", assessor);
                        //obj_log.put("Weather", tweets); // json to be logged to MySQL table
                        array.add(tweets);
                    }
                    // if the weather suggestion has to be added to the JSON returned by this function
                    if (!empty) {
                        array.add(obj);
                    }
                    break;
                }

                obj.put("group", group); // original group name (english)
                HashMap<String, String> langMap = (HashMap<String, String>) groups_langs.get(group);
                if (language.equalsIgnoreCase("en")) {
                    obj.put("label", group);
                } else {
                    obj.put("label", langMap != null ? langMap.get(language) : group); // translated group name in the language specified by lang
                }
                obj.put("priority", i);
                if (assessor == 1) {
                    obj.put("assessor", 1);
                }
                JSONArray json_array = new JSONArray();
                ArrayList<HashMap<String, String>> tmp = serviceURIsList.get(group);
                if (tmp != null && !group.equals("Weather")) {
                    tmp = (ArrayList<HashMap<String, String>>) tmp;
                    for (HashMap<String, String> serviceURIMap : tmp) {
                        //long itemID = getServiceID(serviceURI);
                        // if the max number of recommendations for this group was already provided in this scope, then continue to next group
                        if (counter == Integer.parseInt(settings.get("max_recommendations_returned_from_rdf_per_group"))) {
                            break;
                        }

                        // get location of serviceUri
                        //JSONObject geometry = (JSONObject) features.get("geometry");
                        //double serviceUriLongitude = Double.parseDouble(((JSONArray) geometry.get("coordinates")).get(0).toString());
                        //double serviceUriLatitude = Double.parseDouble(((JSONArray) geometry.get("coordinates")).get(1).toString());
                        // TODO REMOVE SETTING DISTANCE FROM THIS SERVICEURI INTO JSON
                        //properties.put("distance", distFrom(latitude, longitude, serviceUriLatitude, serviceUriLongitude)); // distance of serviceURI in km from (latitude, longitude)
                        // if the result was not already provided the insert it in the json array
                        // tmp[0] = timestamp, tmp[1] = {0 = not added recently, 1 = added recently}
                        String type = serviceURIMap.get("subclass").indexOf("#Event") != -1 ? "Event" : "Service";
                        String[] t;
                        if (aroundme != null && aroundme.equals("true")) {
                            t = null;
                        } else {
                            t = alreadyRecommended(conn, userID, group, serviceURIMap.get("serviceURI"), serviceURIMap.get("name"), serviceURIMap.get("address"), serviceURIMap.get("civic"), type, timestamp);
                        }
                        if (t == null || t[1].equals("0")) {
                            // get serviceUri metadata
                            String url = settings.get("service_map_url") + "?serviceUri=" + serviceURIMap.get("serviceURI") + "&format=json" + (group.equals("Bus") ? "&realtime=false" : "");
                            JSONObject json = getJSON(url);
                            if (json == null) {
                                continue;
                            }
                            /*JSONObject srv = (JSONObject) json.get("Service");
                             JSONObject features = null;
                             String type = "Service";
                             // if Service is null, maybe this is an Event
                             if (srv == null) {
                             type = "Event";
                             srv = (JSONObject) json.get("Event");
                             }
                             // if Service is null then continue
                             if (srv == null) {
                             continue;
                             }
                             if (srv.get("features") != null) {
                             features = (JSONObject) ((JSONArray) srv.get("features")).get(0);
                             }
                             if (features == null) {
                             continue;
                             }*/
                            json_array.add(json);
                            //System.out.println("ID: " + itemID);
                            counter++;
                            // increment the provided recommendations
                            nrecommendations++;

                            // insert this recommendation (or update its timestamp) in the MySQL database
                            PreparedStatement preparedStatement;
                            if (timestamp == null) {
                                preparedStatement = conn.prepareStatement("REPLACE INTO recommender.recommendations SET user_id = ?, serviceURI = ?, name = ?, address = ?, civic = ?, timestamp = CURRENT_TIMESTAMP");
                                preparedStatement.setLong(1, userID);
                                preparedStatement.setString(2, serviceURIMap.get("serviceURI"));
                                preparedStatement.setString(3, serviceURIMap.get("name"));
                                preparedStatement.setString(4, serviceURIMap.get("address"));
                                preparedStatement.setString(5, serviceURIMap.get("civic"));
                            } else {
                                preparedStatement = conn.prepareStatement("REPLACE INTO recommender.recommendations SET user_id = ?, serviceURI = ?, name = ?, address = ?, civic = ?, timestamp = ?");
                                preparedStatement.setLong(1, userID);
                                preparedStatement.setString(2, serviceURIMap.get("serviceURI"));
                                preparedStatement.setString(3, serviceURIMap.get("name"));
                                preparedStatement.setString(4, serviceURIMap.get("address"));
                                preparedStatement.setString(5, serviceURIMap.get("civic"));
                                preparedStatement.setString(6, timestamp);
                            }
                            preparedStatement.executeUpdate();
                        }
                    }
                    if (json_array.size() > 0) {
                        obj.put("suggestions", json_array);
                        obj_log.put(group, json_array); // json to be logged to MySQL table
                        empty = false; // means that this suggestions have to be added to the JSON returned by this function
                    }
                } // if this group is Weather and the user did not disliked it (within the time period defined in the setting ignore_dislike_groups_days)
                else if (group.equals("Weather") && !dislikedGroupsList.contains("Weather")) {
                    // insert weather, this is not part of groups and it is always provided
                    JSONObject weather = getWeather(latitude, longitude);
                    if (weather != null) {
                        obj.put("suggestions", weather);
                        obj_log.put("Weather", weather); // json to be logged to MySQL table
                        empty = false; // means that this suggestions have to be added to the JSON returned by this function
                        // increment the provided recommendation
                        if (weather.get("ERROR") == null && weather.size() > 0) {
                            nrecommendations_weather++;
                        }
                    }
                } // if this group is a Twitter group and the user did not disliked it (within the time period defined in the setting ignore_dislike_groups_days)
                else if (((group.equals("Twitter1") && !dislikedGroupsList.contains("Twitter1"))
                        || (group.equals("Twitter2") && !dislikedGroupsList.contains("Twitter2"))
                        || (group.equals("Twitter3") && !dislikedGroupsList.contains("Twitter3")))
                        && version != null) {
                    JSONObject tweets_json_parsed = getTweets(user, profile, group, assessor);
                    //JSONArray tweets_json_parsed_tmp = (JSONArray) tweets_json_parsed.get("suggestions");
                    //obj_log.put(group, tweets_json_parsed_tmp);
                    array.add(tweets_json_parsed);
                }
                /*if (json_array.size() > 0) {
                 System.out.println("Recommended Items for group " + groups_all.get(i) + " : " + json_array.size());
                 }*/
                // if some suggestions have to be added to the JSON returned by this function
                if (!empty) {
                    array.add(obj);
                }
            }

            timing.put("getRecommendationsJSON", (System.currentTimeMillis() - time));

            //if log is true, then log this recommendation to recommender.recommendations_log MySQL table
            // if timestamp is not null, then log this recommendation to recommender.recommendations_log MySQL table
            if (settings.get("log_recommendations").equals("true")) {
                PreparedStatement pstmt;
                // if log recommendations timing is true, then log timing
                String log_recommendations_timing = "";
                long total_timing = 0;
                if (settings.get("log_recommendations_timing").equalsIgnoreCase("true")) {
                    total_timing = timing.get("init") + timing.get("loadSettings") + timing.get("loadGroups")
                            + timing.get("updateUserProfile") + timing.get("setUserPreferences") + timing.get("getRecommendationsJSON")
                            + timing.get("loadGroupsLangs") + timing.get("refreshRecommender") + timing.get("getRecommendations");
                    log_recommendations_timing = ", init_time = " + timing.get("init")
                            + ", loadSettings_time = " + timing.get("loadSettings")
                            + ", loadGroups_time = " + timing.get("loadGroups")
                            + ", updateUserProfile_time = " + timing.get("updateUserProfile")
                            + ", setUserPreferences_time = " + timing.get("setUserPreferences")
                            + ", getRecommendationsJSON_time = " + timing.get("getRecommendationsJSON")
                            + ", loadGroupsLangs_time = " + timing.get("loadGroupsLangs")
                            + ", refreshRecommender_time = " + timing.get("refreshRecommender")
                            + ", getRecommendations_time = " + timing.get("getRecommendations")
                            + ", total_time = " + total_timing;
                }

                // convert disliked groups to comma separated list
                String dislikedGroups = "";
                if (dislikedGroupsList != null) {
                    for (String dislikedGroup : dislikedGroupsList) {
                        dislikedGroups += ";" + dislikedGroup;
                    }
                }
                // convert disliked subclasses to comma separated list
                String dislikedSubclasses = "";
                if (dislikedSubclassesList != null) {
                    for (String dislikedSubclass : dislikedSubclassesList) {
                        dislikedSubclasses += ";" + dislikedSubclass;
                    }
                }
                dislikedGroups = dislikedGroups.length() > 0 ? dislikedGroups.substring(1) : "";
                dislikedSubclasses = dislikedSubclasses.length() > 0 ? dislikedSubclasses.substring(1) : "";
                // if timestamp is not null, then insert it into MySQL table
                if (timestamp != null) {
                    pstmt = conn.prepareStatement("INSERT INTO recommender.recommendations_log SET user = ?, profile = ?, recommendations = ?, nrecommendations = ?, nrecommendations_weather = ?, nrecommendations_total = ?, distance = ?, latitude = ?, longitude = ?, sparql = ?, dislikedSubclasses = ?, dislikedGroups = ?, mode = ?, timestamp = ?, appID, ?, version = ?, language =?, uid2 = ?" + log_recommendations_timing + ", aroundme = ?, svdEnabled = ?");
                    pstmt.setString(1, user);
                    pstmt.setString(2, profile);
                    pstmt.setString(3, obj_log.toString());
                    pstmt.setInt(4, nrecommendations);
                    pstmt.setInt(5, nrecommendations_weather);
                    pstmt.setInt(6, nrecommendations + nrecommendations_weather);
                    pstmt.setDouble(7, distance);
                    pstmt.setDouble(8, latitude);
                    pstmt.setDouble(9, longitude);
                    pstmt.setString(10, sparql);
                    pstmt.setString(11, dislikedSubclasses);
                    pstmt.setString(12, dislikedGroups);
                    pstmt.setString(13, mode != null ? mode : "");
                    pstmt.setString(14, timestamp);
                    pstmt.setString(15, appID);
                    pstmt.setString(16, version);
                    pstmt.setString(17, language);
                    pstmt.setString(18, uid2);
                    pstmt.setInt(19, aroundme != null && aroundme.equalsIgnoreCase("true") ? 1 : 0);
                    pstmt.setInt(20, svdEnabled != null && svdEnabled.equalsIgnoreCase("true") ? 1 : 0);
                } else {
                    pstmt = conn.prepareStatement("INSERT INTO recommender.recommendations_log SET user = ?, profile = ?, recommendations = ?, nrecommendations = ?, nrecommendations_weather = ?, nrecommendations_total = ?, distance = ?, latitude = ?, longitude = ?, sparql = ?, dislikedSubclasses = ?, dislikedGroups = ?, mode = ?, appID = ?, version = ?, language = ?, uid2 = ?" + log_recommendations_timing + ", aroundme = ?, svdEnabled = ?");
                    pstmt.setString(1, user);
                    pstmt.setString(2, profile);
                    pstmt.setString(3, obj_log.toString());
                    pstmt.setInt(4, nrecommendations);
                    pstmt.setInt(5, nrecommendations_weather);
                    pstmt.setInt(6, nrecommendations + nrecommendations_weather);
                    pstmt.setDouble(7, distance);
                    pstmt.setDouble(8, latitude);
                    pstmt.setDouble(9, longitude);
                    pstmt.setString(10, sparql);
                    pstmt.setString(11, dislikedSubclasses);
                    pstmt.setString(12, dislikedGroups);
                    pstmt.setString(13, mode != null ? mode : "");
                    pstmt.setString(14, appID);
                    pstmt.setString(15, version);
                    pstmt.setString(16, language);
                    pstmt.setString(17, uid2);
                    pstmt.setInt(18, aroundme != null && aroundme.equalsIgnoreCase("true") ? 1 : 0);
                    pstmt.setInt(19, svdEnabled != null && svdEnabled.equalsIgnoreCase("true") ? 1 : 0);
                }
                pstmt.executeUpdate();
            }
        } catch (NumberFormatException | SQLException | JSONException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
        return array.toJSONString();
    }

    // get data for recommendations from ServiceMap for a group
    // this method return a list of recommendations, grouped per groups (reported in the table 'groups' of the MySQL database), as defined for each user profile (e.g. student)
    // the key of the serviceURIsList is the user profile, and the value is an array list of recommendations
    public static String getRecommendationsJSONForGroup(HashMap<String, ArrayList<HashMap<String, String>>> serviceURIsList, String user, String profile, String group, String language, String mode, double latitude, double longitude, String sparql, double distance, String timestamp, ArrayList<String> dislikedSubclassesList, String version, String appID, String uid2, HashMap<String, Long> timing, String svdEnabled) {
        // Extension of the basic JSONObject. This class allows control of the serialization order of attributes
        // The order in which items are put into the instance controls the order in which they are serialized out. For example, the last item put is the last item serialized.
        long time = System.currentTimeMillis();
        OrderedJSONObject obj_log = new OrderedJSONObject();
        JSONArray array = new JSONArray();
        Connection conn = null;
        int nrecommendations = 0;
        int nrecommendations_weather = 0;
        try {
            conn = getConnection();
            long[] assessor_userID = getUserIDAssessor(user, profile, null);
            long userID = assessor_userID[1];
            long assessor = assessor_userID[0];
            int counter = 0;
            boolean empty = true; // flag that indicates if there are suggestions in this group that have to be added to the JSON returned by this function
            JSONObject obj = new JSONObject();

            // if recommendations are empty, check if Weather has to be provided and then exit from the for cycle
            if (serviceURIsList == null) {
                if (group.equalsIgnoreCase("Weather")) {
                    // insert weather
                    JSONObject weather = getWeather(latitude, longitude);
                    if (weather != null) {
                        obj.put("suggestions", weather);
                        obj_log.put("Weather", weather); // json to be logged to MySQL table
                        empty = false; // means that this suggestions have to be added to the JSON returned by this function
                        // increment the provided recommendation
                        if (weather.get("ERROR") == null && weather.size() > 0) {
                            nrecommendations_weather++;
                        }
                    }
                }
            }

            obj.put("group", group); // original group name (english)
            HashMap<String, String> langMap = (HashMap<String, String>) groups_langs.get(group);
            if (language.equalsIgnoreCase("en")) {
                obj.put("label", group);
            } else {
                obj.put("label", langMap != null ? langMap.get(language) : group); // translated group name in the language specified by lang
            }
            obj.put("priority", getPriorityForGroup(profile, group));
            if (assessor == 1) {
                obj.put("assessor", 1);
            }
            JSONArray json_array = new JSONArray();
            ArrayList<HashMap<String, String>> tmp = serviceURIsList != null ? serviceURIsList.get(group) : null;
            if (tmp != null && !group.equalsIgnoreCase("Weather") && !group.equalsIgnoreCase("Twitter1") && !group.equalsIgnoreCase("Twitter2") && !group.equalsIgnoreCase("Twitter3")) {
                tmp = (ArrayList<HashMap<String, String>>) tmp;
                for (HashMap<String, String> serviceURIMap : tmp) {
                    //long itemID = getServiceID(serviceURI);
                    // if the max number of recommendations for this group was already provided in this scope, then continue to next group
                    if (counter == Integer.parseInt(settings.get("max_recommendations_returned_from_rdf_per_group"))) {
                        break;
                    }
                    // get serviceUri metadata
                    String url = settings.get("service_map_url") + "?serviceUri=" + serviceURIMap.get("serviceURI") + "&format=json" + (group.equals("Bus") ? "&realtime=false" : "");
                    JSONObject json = getJSON(url);
                    if (json == null) {
                        continue;
                    }
                    json_array.add(json);
                    counter++;
                    // increment the provided recommendations
                    nrecommendations++;

                    // insert this recommendation (or update its timestamp) in the MySQL database
                    PreparedStatement preparedStatement;
                    if (timestamp == null) {
                        preparedStatement = conn.prepareStatement("REPLACE INTO recommender.recommendations SET user_id = ?, serviceURI = ?, name = ?, address = ?, civic = ?, timestamp = CURRENT_TIMESTAMP");
                        preparedStatement.setLong(1, userID);
                        preparedStatement.setString(2, serviceURIMap.get("serviceURI"));
                        preparedStatement.setString(3, serviceURIMap.get("name"));
                        preparedStatement.setString(4, serviceURIMap.get("address"));
                        preparedStatement.setString(5, serviceURIMap.get("civic"));
                    } else {
                        preparedStatement = conn.prepareStatement("REPLACE INTO recommender.recommendations SET user_id = ?, serviceURI = ?, name = ?, address = ?, civic = ?, timestamp = ?");
                        preparedStatement.setLong(1, userID);
                        preparedStatement.setString(2, serviceURIMap.get("serviceURI"));
                        preparedStatement.setString(3, serviceURIMap.get("name"));
                        preparedStatement.setString(4, serviceURIMap.get("address"));
                        preparedStatement.setString(5, serviceURIMap.get("civic"));
                        preparedStatement.setString(6, timestamp);
                    }
                    preparedStatement.executeUpdate();
                }
                if (json_array.size() > 0) {
                    obj.put("suggestions", json_array);
                    obj_log.put(group, json_array); // json to be logged to MySQL table
                    empty = false; // means that this suggestions have to be added to the JSON returned by this function
                }
            }
            // if some twitter suggestions have to be added to the JSON returned by this function
            if ((group.equals("Twitter1") || group.equals("Twitter2") || group.equals("Twitter3")) && version != null) {
                obj = getTweets(user, profile, group, assessor);
                //JSONArray tweets_json_parsed_tmp = (JSONArray) obj.get("suggestions");
                //obj_log.put(group, tweets_json_parsed_tmp);
                empty = false;
            }
            if (!empty) {
                array.add(obj);
            }

            timing.put("getRecommendationsJSON", (System.currentTimeMillis() - time));

            //if log is true, then log this recommendation to recommender.recommendations_log MySQL table
            // if timestamp is not null, then log this recommendation to recommender.recommendations_log MySQL table
            if (settings.get("log_recommendations").equals("true")) {
                PreparedStatement pstmt;
                // if log recommendations timing is true, then log timing
                String log_recommendations_timing = "";
                if (settings.get("log_recommendations_timing").equalsIgnoreCase("true")) {
                    log_recommendations_timing = ", init_time = " + timing.get("init")
                            + ", loadSettings_time = " + timing.get("loadSettings")
                            + ", loadGroups_time = " + timing.get("loadGroups")
                            + ", updateUserProfile_time = " + timing.get("updateUserProfile")
                            + ", setUserPreferences_time = " + timing.get("setUserPreferences")
                            + ", getRecommendationsJSON_time = " + timing.get("getRecommendationsJSON")
                            + ", loadGroupsLangs_time = " + timing.get("loadGroupsLangs")
                            + ", refreshRecommender_time = " + timing.get("refreshRecommender")
                            + ", getRecommendations_time = " + timing.get("getRecommendations");
                }

                // convert disliked groups to comma separated list
                String dislikedGroups = "";
                // convert disliked subclasses to comma separated list
                String dislikedSubclasses = "";
                if (dislikedSubclassesList != null) {
                    for (String dislikedSubclass : dislikedSubclassesList) {
                        dislikedSubclasses += ";" + dislikedSubclass;
                    }
                }
                dislikedSubclasses = dislikedSubclasses.length() > 0 ? dislikedSubclasses.substring(1) : "";
                // if timestamp is not null, then insert it into MySQL table
                if (timestamp != null) {
                    pstmt = conn.prepareStatement("INSERT INTO recommender.recommendations_log SET user = ?, profile = ?, recommendations = ?, nrecommendations = ?, nrecommendations_weather = ?, nrecommendations_total = ?, distance = ?, latitude = ?, longitude = ?, sparql = ?, dislikedSubclasses = ?, dislikedGroups = ?, requestedGroup = ?, mode = ?, timestamp = ?, appID = ?, version = ?, language = ?, uid2 = ?" + log_recommendations_timing + ", svdEnabled = ?");
                    pstmt.setString(1, user);
                    pstmt.setString(2, profile);
                    pstmt.setString(3, obj_log.toString());
                    pstmt.setInt(4, nrecommendations);
                    pstmt.setInt(5, nrecommendations_weather);
                    pstmt.setInt(6, nrecommendations + nrecommendations_weather);
                    pstmt.setDouble(7, distance);
                    pstmt.setDouble(8, latitude);
                    pstmt.setDouble(9, longitude);
                    pstmt.setString(10, sparql);
                    pstmt.setString(11, dislikedSubclasses);
                    pstmt.setString(12, dislikedGroups);
                    pstmt.setString(13, group);
                    pstmt.setString(14, mode != null ? mode : "");
                    pstmt.setString(15, timestamp);
                    pstmt.setString(16, appID);
                    pstmt.setString(17, version);
                    pstmt.setString(18, language);
                    pstmt.setString(19, uid2);
                    pstmt.setInt(20, svdEnabled != null && svdEnabled.equalsIgnoreCase("true") ? 1 : 0);
                } else {
                    pstmt = conn.prepareStatement("INSERT INTO recommender.recommendations_log SET user = ?, profile = ?, recommendations = ?, nrecommendations = ?, nrecommendations_weather = ?, nrecommendations_total = ?, distance = ?, latitude = ?, longitude = ?, sparql = ?, dislikedSubclasses = ?, dislikedGroups = ?, requestedGroup = ?, mode = ?, appID = ?, version = ?, language = ?, uid2 = ?" + log_recommendations_timing + ", svdEnabled = ?");
                    pstmt.setString(1, user);
                    pstmt.setString(2, profile);
                    pstmt.setString(3, obj_log.toString());
                    pstmt.setInt(4, nrecommendations);
                    pstmt.setInt(5, nrecommendations_weather);
                    pstmt.setInt(6, nrecommendations + nrecommendations_weather);
                    pstmt.setDouble(7, distance);
                    pstmt.setDouble(8, latitude);
                    pstmt.setDouble(9, longitude);
                    pstmt.setString(10, sparql);
                    pstmt.setString(11, dislikedSubclasses);
                    pstmt.setString(12, dislikedGroups);
                    pstmt.setString(13, group);
                    pstmt.setString(14, mode != null ? mode : "");
                    pstmt.setString(15, appID);
                    pstmt.setString(16, version);
                    pstmt.setString(17, language);
                    pstmt.setString(18, uid2);
                    pstmt.setInt(19, svdEnabled != null && svdEnabled.equalsIgnoreCase("true") ? 1 : 0);
                }
                pstmt.executeUpdate();

            }
        } catch (NumberFormatException | SQLException | JSONException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
        return array.toJSONString();
    }

    // get recommendations (services) for a group and the sparql query used to get them
    // object[0] = recommendations (HashMap<String, ArrayList<String>>), object[1] sparql query
    // this method always returns recommendations if they are present within the radius specified by the user
    public static Object[] getRecommendationsForGroup(String user, String profile, String group, String svdEnabled, double latitude, double longitude, double distance) {
        HashMap<String, ArrayList<HashMap<String, String>>> result = new HashMap<>();
        String sparql = "";
        int nrecs = Integer.parseInt(settings.get("max_recommendations_returned_from_rdf_per_group"));
        ArrayList<String> dislikedSubclasses = null;
        if (group.equalsIgnoreCase("Weather") || group.equalsIgnoreCase("Twitter1") || group.equalsIgnoreCase("Twitter2") || group.equalsIgnoreCase("Twitter3")) {
            return new Object[]{null, dislikedSubclasses, sparql};
        }
        // get the previously recommended Items
        ArrayList<String> previouslyRecommendedItems = previouslyRecommended(getUserID(user, profile, null), group);
        try {
            // get the list of recommended subclasses
            Object[] recommendations = svdRecommend(user, profile, group, svdEnabled, latitude, longitude, distance);
            ArrayList<String> recommendations_list = (ArrayList<String>) recommendations[0];
            dislikedSubclasses = (ArrayList<String>) recommendations[1];

            // add in any case all the macroclasses or subclasses belonging to this group, if not disliked
            /*if (!profile.equalsIgnoreCase("all")) {
             ArrayList<String> subclasses = users_profiles.get(profile);
             for (String subclass : subclasses) {
             String g = getSubclassGroup(subclass);
             if (g.equalsIgnoreCase(group) && !dislikedSubclasses.contains(subclass)) {
             String t = subclass.equalsIgnoreCase("Bus") ? "BusStop" : subclass;
             t = subclass.equalsIgnoreCase("Events") ? "Event" : subclass;
             recommendations_list.add(t);
             }
             }
             } else {
             // macroclasses cannot be disliked
             ArrayList<String> macroclasses = getMacroclasses();
             for (String macroclass_subclass : macroclasses) {
             HashMap<String, String> tmp = categories.get(macroclass_subclass);
             if (tmp.get("group").equalsIgnoreCase(group) && !macroclass_subclass.equalsIgnoreCase("Event") && !macroclass_subclass.equalsIgnoreCase("Events")) {
             String t = macroclass_subclass.equalsIgnoreCase("Bus") ? "BusStop" : macroclass_subclass;
             recommendations_list.add(t);
             }
             }
             }*/
            // add in any case all the macroclasses belonging to this group
            ArrayList<String> groupMacroclasses = getGroupMacroclasses(group);
            for (String groupMacroclass : groupMacroclasses) {
                String t = groupMacroclass.equalsIgnoreCase("Bus") ? "BusStop" : groupMacroclass;
                t = groupMacroclass.equalsIgnoreCase("Events") ? "Event" : groupMacroclass;
                recommendations_list.add(t);
            }

            JSONObject results = null;
            if (recommendations_list.size() > 0) {
                sparql = getSPARQLQueryForGroup(recommendations_list, group, latitude, longitude, distance);
                String url = settings.get("rdf_url") + "?query=" + URLEncoder.encode(sparql, "UTF-8") + "&format=json";
                results = getJSON(url);
            }

            // if results are empty then return
            if (results == null || results.get("results") == null) {
                return new Object[]{null, dislikedSubclasses, sparql};
            }

            results = (JSONObject) results.get("results");
            JSONArray list = (JSONArray) results.get("bindings");
            int i = 0;
            for (Object obj : list) {
                // if a number of items = nrecs has been added to the list of results exit the cycle
                if (i == nrecs) {
                    break;
                }
                JSONObject o = (JSONObject) obj;
                o = (JSONObject) o.get("s");
                String s = (String) o.get("value"); // serviceUri
                o = (JSONObject) obj;
                o = (JSONObject) o.get("t");
                String t = (String) o.get("value"); // subclass
                o = (JSONObject) obj;
                o = (JSONObject) o.get("dist");
                String dist = (String) o.get("value"); // distance
                String name = "";
                String address = "";
                String civic = "";
                o = (JSONObject) obj;
                if (o.get("name") != null) {
                    o = (JSONObject) o.get("name");
                    name = (String) o.get("value"); // name
                }
                o = (JSONObject) obj;
                if (o.get("address") != null) {
                    o = (JSONObject) o.get("address");
                    address = (String) o.get("value"); // address
                }
                if (o.get("civic") != null) {
                    o = (JSONObject) obj;
                    o = (JSONObject) o.get("civic");
                    civic = (String) o.get("value"); // civic
                }

                // if the serviceUri is not contained in the previously recommended items add it to the list of results
                if (!previouslyRecommendedItems.contains(s) || list.size() <= nrecs) {
                    // put the service in the map
                    HashMap<String, String> tmp = new HashMap<>();
                    tmp.put("serviceURI", s);
                    tmp.put("distance", dist);
                    tmp.put("subclass", t);
                    tmp.put("name", name);
                    tmp.put("address", address);
                    tmp.put("civic", civic);
                    if (result.get(group) == null) {
                        ArrayList<HashMap<String, String>> services_list = new ArrayList<>();
                        services_list.add(tmp);
                        result.put(group, services_list);
                        i++;
                    } else {
                        result.get(group).add(tmp);
                        i++;

                    }
                }
            }
        } catch (UnsupportedEncodingException | NumberFormatException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
        return new Object[]{result, dislikedSubclasses, sparql};
    }

    // check how much the recommender misses the real interaction strength on average
    // Evaluates the quality of a Recommender's recommendations.
    // The range of values that may be returned depends on the implementation,
    // but lower values must mean better recommendations, with 0 being the lowest / best
    // possible evaluation, meaning a perfect match. This method does not accept a Recommender directly,
    // but rather a RecommenderBuilder which can build the Recommender to test on top of a given DataModel.
    // Implementations will take a certain percentage of the preferences supplied by the given DataModel as
    // "training data". This is typically most of the data, like 90%. This data is used to produce recommendations,
    // and the rest of the data is compared against estimated preference values to see how much the Recommender's
    // predicted preferences match the user's real preferences. Specifically, for each user, this percentage of the
    // user's ratings are used to produce recommendations, and for each user, the remaining preferences are compared
    // against the user's real preferences.
    // For large datasets, it may be desirable to only evaluate based on a small percentage of the data. evaluationPercentage
    // controls how many of the DataModel's users are used in evaluation.
    // To be clear, trainingPercentage and evaluationPercentage are not related. They do not need to add up to 1.0, for example.
    // If you run this test multiple times, you will get different results, because the splitting into trainingset and testset is done randomly.
    // Returns a "score" representing how well the Recommender's estimated preferences match real values; lower scores mean a better match and 0 is a perfect match
    public static void test() {
        try {
            // Generates repeatable results
            // This is only used in such examples, and unit tests, to
            // guarantee repeatable results. Don’t use it in the real code
            // Without the call to RandomUtils.useTestSeed(), the result you see would vary significantly
            // due to the random selection of training data and test data
            //RandomUtils.useTestSeed();
            //DataModel model = new FileDataModel(new File("C:\\Users\\cenni\\Downloads\\dataset.csv"));
            DataModel model = rdm;
            RecommenderEvaluator evaluator = new AverageAbsoluteDifferenceRecommenderEvaluator();
            //RecommenderIRStatsEvaluator evaluator = new GenericRecommenderIRStatsEvaluator();
            RecommenderBuilder builder = new MyRecommenderBuilder();
            double result = evaluator.evaluate(builder, null, model, 0.7, 1.0);
            System.out.println(result);
            /*IRStatistics stats = evaluator.evaluate(
             builder, null, model, null, 2,
             GenericRecommenderIRStatsEvaluator.CHOOSE_THRESHOLD,
             1.0);
             System.out.println(stats.getPrecision());
             System.out.println(stats.getRecall());*/

        } catch (TasteException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
    }

    public static void evaluateRecommender() {
        try {
            RandomUtils.useTestSeed();
            DataModel model = new MySQLJDBCDataModel(
                    mysql_datasource, "assessment_new", "user_id",
                    "item_id", "preference", "timestamp");
            RecommenderIRStatsEvaluator evaluator = new GenericRecommenderIRStatsEvaluatorCustom();
            RecommenderBuilder recommenderBuilder = new MyRecommenderBuilder();
            IRStatistics stats = evaluator.evaluate(
                    recommenderBuilder, null, model, null, 10,
                    GenericRecommenderIRStatsEvaluator.CHOOSE_THRESHOLD,
                    1.0);
            System.out.println("Precision: " + stats.getPrecision());
            System.out.println("Recall: " + stats.getRecall());
        } catch (TasteException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
    }

    // check if a recommendation was already provided to the user in the last x days (property do_not_repeat_GROUP where GROUP is the group name), MySQL connection is closed by calling function
    // to avoid duplicates, services are checked by their name, address and civic number instead of the serviceURI
    public static String[] alreadyRecommended(Connection conn, long userID, String group, String serviceURI, String name, String address, String civic, String type, String timestamp) {
        try {
            String setting = settings.get("do_not_repeat_" + group.replace(" ", "_"));
            int do_not_recommend_days = Integer.parseInt(setting != null ? setting : "0");
            if (do_not_recommend_days == 0 || group.equalsIgnoreCase("Bus")) { // nearest buses are always recommended
                return null;
            }
            PreparedStatement preparedStatement;
            // if this is a service, search if it was already recommended by name, address, civic
            if (type.equals("Service")) {
                if (timestamp == null) {
                    preparedStatement = conn.prepareStatement("SELECT UNIX_TIMESTAMP(timestamp) AS unix_timestamp,  (timestamp >= NOW() - INTERVAL ? DAY) AS recent FROM recommender.recommendations WHERE user_id = ? AND name = ? AND address = ? AND civic = ?");
                    preparedStatement.setInt(1, do_not_recommend_days);
                    preparedStatement.setLong(2, userID);
                    preparedStatement.setString(3, name);
                    preparedStatement.setString(4, address);
                    preparedStatement.setString(5, civic);
                } else {
                    preparedStatement = conn.prepareStatement("SELECT UNIX_TIMESTAMP(timestamp) AS unix_timestamp,  (timestamp >= ? - INTERVAL ? DAY) AS recent FROM recommender.recommendations WHERE user_id = ? AND name = ? AND address = ? AND civic = ?");
                    preparedStatement.setString(1, timestamp);
                    preparedStatement.setInt(2, do_not_recommend_days);
                    preparedStatement.setLong(3, userID);
                    preparedStatement.setString(4, name);
                    preparedStatement.setString(5, address);
                    preparedStatement.setString(6, civic);
                }
                ResultSet rs = preparedStatement.executeQuery();
                while (rs.next()) {
                    return new String[]{rs.getString("unix_timestamp"), rs.getString("recent")};
                }
            } // if this is an event, search if it was already recommended by serviceUri
            else if (type.equals("Event")) {
                if (timestamp == null) {
                    preparedStatement = conn.prepareStatement("SELECT UNIX_TIMESTAMP(timestamp) AS unix_timestamp,  (timestamp >= NOW() - INTERVAL ? DAY) AS recent FROM recommender.recommendations WHERE user_id = ? AND serviceURI = ?");
                    preparedStatement.setInt(1, do_not_recommend_days);
                    preparedStatement.setLong(2, userID);
                    preparedStatement.setString(3, serviceURI);
                } else {
                    preparedStatement = conn.prepareStatement("SELECT UNIX_TIMESTAMP(timestamp) AS unix_timestamp,  (timestamp >= ? - INTERVAL ? DAY) AS recent FROM recommender.recommendations WHERE user_id = ? AND serviceURI = ?");
                    preparedStatement.setString(1, timestamp);
                    preparedStatement.setInt(2, do_not_recommend_days);
                    preparedStatement.setLong(3, userID);
                    preparedStatement.setString(4, serviceURI);
                }
                ResultSet rs = preparedStatement.executeQuery();
                while (rs.next()) {
                    return new String[]{rs.getString("unix_timestamp"), rs.getString("recent")};

                }
            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
        return null;
    }

    // get the list of previosuly recommendation items to the user
    public static ArrayList<String> previouslyRecommended(long userID, String group) {
        ArrayList<String> result = new ArrayList<>();
        // if this group is Bus, then return the empty array list, since nearest buses must be recommended and not excluded with the previously recommended list
        if (group.equalsIgnoreCase("Bus")) {
            return result;
        }
        Connection conn = null;
        try {
            conn = getConnection();
            //int nrecs = Integer.parseInt(settings.get("max_recommendations_returned_from_rdf_per_group"));
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT serviceUri FROM recommender.recommendations WHERE user_id = ? ORDER BY timestamp DESC LIMIT 1000");
            preparedStatement.setLong(1, userID);
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                result.add(rs.getString("serviceUri"));

            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();

                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return result;
    }

    // load services with their id and opening hours into memory from MySQL database
    public static void loadMacroclassOpenings(String macroclass) {
        Connection conn = null;
        if (macroclass_hours == null) {
            macroclass_hours = new HashMap<>();
        }
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT opening1, closing1, opening2, closing2 FROM recommender.categories_hours WHERE macroclass = ?");
            preparedStatement.setString(1, macroclass);
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                HashMap<String, String> tmp = new HashMap<>();
                tmp.put("opening1", rs.getString("opening1"));
                tmp.put("closing1", rs.getString("closing1"));
                tmp.put("opening2", rs.getString("opening2"));
                tmp.put("closing2", rs.getString("closing2"));
                macroclass_hours.put(rs.getString("macroclass"), tmp);

            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();

                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
    }

    // load macro classes, subclasses, events into a HashMap from MySQL database
    public static void loadCategories() {
        Connection conn = null;
        if (categories == null) {
            categories = new HashMap<>();
        }
        if (categories_ids == null) {
            categories_ids = new TreeBidiMap();
        }
        try {
            conn = getConnection();
            // load macro classes (includes bus stops and events)
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT a.id, a.key, b.group FROM recommender.categories a LEFT JOIN recommender.categories_groups b ON a.key = b.key WHERE macroclass = ''");
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                HashMap<String, String> tmp = new HashMap<>();
                tmp.put("type", "macroclass");
                tmp.put("group", rs.getString("group"));
                categories.put(rs.getString("key"), tmp);
                categories_ids.put(rs.getString("key"), rs.getLong("id"));
            }
            // load sub classes
            preparedStatement = conn.prepareStatement("SELECT a.id, a.key, a.macroclass, b.group FROM recommender.categories a LEFT JOIN recommender.categories_groups b ON a.key = b.key WHERE macroclass != ''");
            rs = preparedStatement.executeQuery();
            while (rs.next()) {
                HashMap<String, String> tmp = new HashMap<>();
                tmp.put("type", "subclass");
                tmp.put("macroclass", rs.getString("macroclass").substring(rs.getString("macroclass").indexOf("#") + 1));
                tmp.put("group", rs.getString("group"));
                categories.put(rs.getString("key"), tmp);
                categories_ids.put(rs.getString("key"), rs.getLong("id"));

            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();

                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
    }

    // get the macroclasses of a group
    public static ArrayList<String> getGroupMacroclasses(String group) {
        Iterator it = categories.keySet().iterator();
        ArrayList<String> groupMacroclasses = new ArrayList<>();
        while (it.hasNext()) {
            String key = (String) it.next();
            HashMap map = categories.get(key);
            if (map.get("type").equals("macroclass") && map.get("group").equals(group)) {
                groupMacroclasses.add(key);
            }
        }
        return groupMacroclasses;
    }

    // get the user id from MySQL database (insert the user if not present)
    public static long getUserID(String user, String profile, String userAgent) {
        Connection conn = null;
        long id = 0;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("INSERT IGNORE INTO recommender.users (user) VALUES (?)");
            preparedStatement.setString(1, user);
            preparedStatement.executeUpdate();

            preparedStatement = conn.prepareStatement("UPDATE recommender.users SET profile = ?" + (userAgent != null ? ", userAgent = ?" : "") + " WHERE user = ?");
            preparedStatement.setString(1, profile);
            if (userAgent != null) {
                preparedStatement.setString(2, userAgent);
                preparedStatement.setString(3, user);
            } else {
                preparedStatement.setString(2, user);
            }
            preparedStatement.executeUpdate();

            preparedStatement = conn.prepareStatement("SELECT id FROM recommender.users WHERE user = ?");
            preparedStatement.setString(1, user);
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                id = rs.getLong("id");
            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return id;
    }

    // get the user id from MySQL database
    public static long getUserID(String user) {
        Connection conn = null;
        long id = 0;
        try {
            conn = getConnection();

            PreparedStatement preparedStatement = conn.prepareStatement("SELECT id FROM recommender.users WHERE user = ?");
            preparedStatement.setString(1, user);
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                id = rs.getLong("id");
            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return id;
    }

    // get the item id from MySQL database (insert the item if not present)
    public static long getItemID(String item) {
        Connection conn = null;
        long id = 0;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("INSERT IGNORE INTO recommender.assessment_items (item) VALUES (?)");
            preparedStatement.setString(1, item);
            preparedStatement.executeUpdate();

            preparedStatement = conn.prepareStatement("SELECT id FROM recommender.assessment_items WHERE item = ?");
            preparedStatement.setString(1, item);
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                id = rs.getLong("id");
            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return id;
    }

    // get the user id from MySQL database (insert the user if not present)
    public static long[] getUserIDAssessor(String user, String profile, String userAgent) {
        Connection conn = null;
        long id = 0;
        long assessor = 0;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("INSERT IGNORE INTO recommender.users (user) VALUES (?)");
            preparedStatement.setString(1, user);
            preparedStatement.executeUpdate();

            preparedStatement = conn.prepareStatement("UPDATE recommender.users SET profile = ?" + (userAgent != null ? ", userAgent = ?" : "") + " WHERE user = ?");
            preparedStatement.setString(1, profile);
            if (userAgent != null) {
                preparedStatement.setString(2, userAgent);
                preparedStatement.setString(3, user);
            } else {
                preparedStatement.setString(2, user);
            }
            preparedStatement.executeUpdate();

            preparedStatement = conn.prepareStatement("SELECT assessor, id FROM recommender.users WHERE user = ?");
            preparedStatement.setString(1, user);
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                id = rs.getLong("id");
                assessor = rs.getLong("assessor");
            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return new long[]{assessor, id};
    }

    // get the user id from MySQL database (insert the user if not present)
    public static String assess(String uid, String serviceURI, String genericID, String type, String vote) {
        Connection conn = null;
        try {
            conn = getConnection();
            long user_id = getUserID(uid);
            long item_id = getItemID(serviceURI != null ? serviceURI : genericID);
            PreparedStatement preparedStatement = conn.prepareStatement("INSERT IGNORE INTO recommender.assessment (user_id, item_id, serviceURI, genericID, type, preference) VALUES (?,?,?,?,?,?)");
            preparedStatement.setLong(1, user_id);
            preparedStatement.setLong(2, item_id);
            preparedStatement.setString(3, serviceURI);
            preparedStatement.setString(4, genericID);
            preparedStatement.setString(5, type);
            preparedStatement.setString(6, vote);
            preparedStatement.executeUpdate();
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return "true";
    }

    // temp function to populate assessment table from the old one
    public static String populateAssessmentOld() {
        Connection conn = null;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement1 = conn.prepareStatement("SELECT * FROM recommender.assessment");
            ResultSet rs = preparedStatement1.executeQuery();
            while (rs.next()) {
                long user_id = getUserID(rs.getString("uid"));
                long item_id = getItemID(rs.getString("serviceURI") != null ? rs.getString("serviceURI") : rs.getString("genericID"));
                PreparedStatement preparedStatement2 = conn.prepareStatement("INSERT IGNORE INTO recommender.assessment_new (user_id, item_id, type, preference) VALUES (?,?,?,?)");
                preparedStatement2.setLong(1, user_id);
                preparedStatement2.setLong(2, item_id);
                preparedStatement2.setString(3, rs.getString("type"));
                preparedStatement2.setString(4, rs.getString("vote"));
                preparedStatement2.executeUpdate();
            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return "true";
    }

    // temp function to populate assessment table from the old one
    public static String populateAssessment() {
        Connection conn = null;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement1 = conn.prepareStatement("SELECT uid, serviceURI FROM recommender.AccessLog WHERE uid IS NOT NULL AND categories IS NOT NULL AND serviceURI LIKE 'http%' AND mode = \"api-service-info\"");
            ResultSet rs = preparedStatement1.executeQuery();
            while (rs.next()) {
                long user_id = getUserID(rs.getString("uid"));
                long item_id = getItemID(rs.getString("serviceURI"));
                PreparedStatement preparedStatement2 = conn.prepareStatement("INSERT INTO recommender.assessment_new (user_id, item_id, type, preference) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE preference = preference + 1");
                preparedStatement2.setLong(1, user_id);
                preparedStatement2.setLong(2, item_id);
                preparedStatement2.setString(3, "recom");
                preparedStatement2.setString(4, "1");
                preparedStatement2.executeUpdate();
            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return "true";
    }

    // get the user profile from MySQL database
    public static String getUserProfile(String user) {
        Connection conn = null;
        String profile = "";
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT profile FROM recommender.users WHERE user = ?");
            preparedStatement.setString(1, user);
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                profile = rs.getString("profile");
            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return profile;
    }

    // get the disliked groups by the user, attention this consider the eventually disliked groups by the user for the time period defined in the setting ignore_dislike_groups_days
    public static ArrayList<String> getDislikedGroups(String user) {
        Connection conn = null;
        ArrayList<String> dislikedGroups = new ArrayList<>();
        int ignore_dislike_groups_days = Integer.parseInt(settings.get("ignore_dislike_groups_days"));
        String group_query_timestamp = ignore_dislike_groups_days > 0 ? " AND timestamp > NOW() - INTERVAL " + ignore_dislike_groups_days + " DAY" : "";
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT dislikedGroup FROM recommender.dislike WHERE user = ? AND dislikedGroup IS NOT NULL" + group_query_timestamp);
            preparedStatement.setString(1, user);
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                dislikedGroups.add(rs.getString("dislikedGroup"));

            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();

                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return dislikedGroups;
    }

    // get the disliked subclasses by the user, attention this consider the eventually disliked subclasses by the user for the time period defined in the setting ignore_dislike_subclass_days
    public static ArrayList<String> getDislikedSubclasses(String user) {
        Connection conn = null;
        ArrayList<String> dislikedSubclasses = new ArrayList<>();
        int ignore_dislike_subclass_days = Integer.parseInt(settings.get("ignore_dislike_subclass_days"));
        String subclass_query_timestamp = ignore_dislike_subclass_days > 0 ? " AND timestamp > NOW() - INTERVAL " + ignore_dislike_subclass_days + " DAY" : "";
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT dislikedSubclass FROM recommender.dislike WHERE user = ? AND dislikedSubclass IS NOT NULL" + subclass_query_timestamp);
            preparedStatement.setString(1, user);
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                dislikedSubclasses.add(rs.getString("dislikedSubclass"));

            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();

                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return dislikedSubclasses;
    }

    // set the user profile name to MySQL database
    public static void updateUserProfile(String user, String profile) {
        Connection conn = null;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("UPDATE recommender.users SET profile = ? WHERE user = ?");
            preparedStatement.setString(1, profile);
            preparedStatement.setString(2, user);
            preparedStatement.executeUpdate();

        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();

                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
    }

    // get a group priority for a user profile
    public static long getPriorityForGroup(String profile, String group) {
        Connection conn = null;
        long id = 0;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = null;
            // all is a reserved word in MySQL
            /*if (profile.equals("all")) {
             preparedStatement = conn.prepareStatement("SELECT `all` FROM recommender.groups WHERE `group` = ?");
             preparedStatement.setString(1, group);
             } else {
             preparedStatement = conn.prepareStatement("SELECT ? FROM recommender.groups WHERE `group` = ?");
             preparedStatement.setString(1, profile);
             preparedStatement.setString(2, group);
             }*/
            preparedStatement = conn.prepareStatement("SELECT `" + profile + "` FROM recommender.groups WHERE `group` = '" + group + "'");
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                id = rs.getLong(profile);

            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();

                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return id;
    }

    // get service id from serviceURI
    /*public static long getServiceID(String serviceURI) {
     Long serviceID = (Long) services.get(serviceURI);
     if (serviceID != null) {
     return serviceID;
     } else {
     insertService(serviceURI);
     loadService(serviceURI);
     return (long) services.get(serviceURI);
     }
     }*/
    // get serviceURI from service id
    /*public static String getService(long id) {
     if (services.getKey(id) != null) {
     return (String) services.getKey(id);
     } else {
     return null;
     }
     }*/
    // get SPARQL query (services within distance)
    public static String getSPARQLQueryOld(ArrayList<String> ctgs, double latitude, double longitude, double distance) {
        String subclasses_sparql = "";
        String macroclasses_sparql = "";
        String result = "";
        int subclasses_counter = 0;
        for (String ctg : ctgs) {
            // this class is a sub class
            HashMap<String, String> tmp = categories.get(ctg);
            if (tmp != null) {
                if (tmp.get("type").equals("subclass") && !ctg.equalsIgnoreCase("Event")) {
                    subclasses_sparql += (subclasses_counter == 0 ? "" : ",") + "km4c:" + ctg;
                    subclasses_counter++;
                } // this class is a macro class
                else if (tmp.get("type").equals("macroclass")) {
                    macroclasses_sparql += " UNION{?s a km4c:" + ctg + " OPTION(inference \"urn:ontology\")}";
                }
            }
        }
        // insert bus stops and events anyway
        //macroclasses_sparql += "UNION{?s a km4c:BusStop OPTION(inference \"urn:ontology\")}";
        macroclasses_sparql += " UNION{?s a km4c:Event OPTION(inference \"urn:ontology\"). ?s <http://schema.org/startDate> ?sd. FILTER(?sd >= NOW())}";

        if (subclasses_counter == 0) {
            macroclasses_sparql = macroclasses_sparql.substring(5);
        } else {
            subclasses_sparql = "{ ?s a ?tp FILTER(?tp IN (" + subclasses_sparql + "))}";
        }
        String optional = "OPTIONAL {{?s schema:name ?name} UNION {?s foaf:name ?name}}\n"
                + "    OPTIONAL {?s schema:streetAddress ?address}\n"
                + "    OPTIONAL {?s km4c:houseNumber ?civic }";
        result = "# RECOMMEND\n"
                + "PREFIX km4c:<http://www.disit.org/km4city/schema#>\n"
                + "PREFIX schema:<http://schema.org/>\n"
                + "SELECT DISTINCT ?s ?t ?name ?address ?civic ?dist WHERE {\n"
                + "?s a ?t.\n"
                + "FILTER(?t!=km4c:RegularService && ?t!=km4c:Service && ?t!=km4c:DigitalLocation && ?t!=km4c:TransverseService)\n"
                + subclasses_sparql
                + macroclasses_sparql
                + " {\n"
                + "   ?s km4c:hasAccess ?entry.\n"
                + "   ?entry geo:lat ?elt.\n"
                + "   ?entry geo:long ?elg.\n"
                + "   ?entry geo:geometry ?geo.\n"
                + "   FILTER(bif:st_distance(?geo, bif:st_point(" + longitude + "," + latitude + ")) <= " + distance + ")\n"
                + "   BIND(bif:st_distance(?geo, bif:st_point(" + longitude + "," + latitude + ")) AS ?dist)\n"
                + "  } UNION {\n"
                + "   ?s geo:lat ?elt.\n"
                + "   ?s geo:long ?elg.\n"
                + "   ?s geo:geometry ?geo.\n"
                + "   FILTER(bif:st_distance(?geo, bif:st_point(" + longitude + "," + latitude + ")) <= " + distance + ")\n"
                + "   BIND(bif:st_distance(?geo, bif:st_point(" + longitude + "," + latitude + ")) AS ?dist)\n"
                + "  }\n"
                + optional
                + "} ORDER BY ?dist";
        //System.out.println(result);
        return result;
    }

    // get SPARQL query (services within distance)
    public static String getSPARQLQuery(ArrayList<String> ctgs, double latitude, double longitude, double distance) {
        String subclasses_sparql = "";
        String macroclasses_sparql = "";
        String result = "";
        int subclasses_counter = 0;
        if (ctgs != null) {
            for (String ctg : ctgs) {
                // this class is a sub class
                HashMap<String, String> tmp = categories.get(ctg);
                if (tmp != null) {
                    if (tmp.get("type").equals("subclass") && !ctg.equalsIgnoreCase("Event")) {
                        subclasses_sparql += (subclasses_counter == 0 ? "" : ",") + "km4c:" + ctg;
                        subclasses_counter++;
                    }// this class is a macro class
                    else if (tmp.get("type").equals("macroclass")) {
                        macroclasses_sparql += " UNION{?s a km4c:" + ctg + " OPTION(inference \"urn:ontology\")}";
                    }
                }
            }
        }
        // insert bus stops and events anyway
        //macroclasses_sparql += "UNION{?s a km4c:BusStop OPTION(inference \"urn:ontology\")}";
        // if ctgs is empty, then use all macroclasses
        if (ctgs == null) {
            macroclasses_sparql = "{?s a km4c:Service OPTION(inference \"urn:ontology\")}";
        }
        macroclasses_sparql += " UNION{?s a km4c:Event OPTION(inference \"urn:ontology\"). ?s <http://schema.org/startDate> ?sd. FILTER(?sd >= NOW())}";

        if (subclasses_counter != 0) {
            subclasses_sparql = "{ ?s a ?tp FILTER(?tp IN (" + subclasses_sparql + "))}";
        }
        if (ctgs != null && subclasses_sparql.equals("")) {
            macroclasses_sparql = macroclasses_sparql.substring(6);
        }
        result = "# RECOMMEND\n"
                + "PREFIX km4c:<http://www.disit.org/km4city/schema#>\n"
                + "PREFIX schema:<http://schema.org/>\n"
                + "PREFIX gtfs:<http://vocab.gtfs.org/terms#>\n"
                + "SELECT DISTINCT ?s ?t ?name ?address ?civic ?dist WHERE {{\n"
                + "SELECT DISTINCT ?s ?t ?name ?address ?civic ?dist WHERE {\n"
                + "?s a ?t.\n"
                + "FILTER(?t!=km4c:RegularService && ?t!=km4c:Service && \n"
                + "?t!=km4c:DigitalLocation && ?t!=km4c:TransverseService && ?t!=gtfs:Stop)\n"
                + subclasses_sparql
                + macroclasses_sparql
                + "\n"
                + "OPTIONAL {\n"
                + "    ?s km4c:hasAccess ?entry.\n"
                + "    ?entry geo:geometry ?geo1.\n"
                + "    FILTER(bif:st_distance(?geo1, \n"
                + "bif:st_point(" + longitude + "," + latitude + ")) <= " + distance + ")\n"
                + "    BIND(bif:st_distance(?geo1, \n"
                + "bif:st_point(" + longitude + "," + latitude + ")) AS ?dist1)\n"
                + "   }\n"
                + "OPTIONAL {\n"
                + "    ?s geo:lat []; geo:long [].\n"
                + "    ?s geo:geometry ?geo2.\n"
                + "    FILTER(bif:st_distance(?geo2, \n"
                + "bif:st_point(" + longitude + "," + latitude + ")) <= " + distance + ")\n"
                + "    BIND(bif:st_distance(?geo2, \n"
                + "bif:st_point(" + longitude + "," + latitude + ")) AS ?dist2)\n"
                + "   }\n"
                + "filter (bound(?dist1) || bound(?dist2))\n"
                + "BIND( IF(bound(?dist1), ?dist1, ?dist2) as ?dist)\n"
                + "\n"
                + "OPTIONAL {{?s schema:name ?name} UNION {?s foaf:name ?name}}\n"
                + "     OPTIONAL {?s schema:streetAddress ?address}\n"
                + "     OPTIONAL {?s km4c:houseNumber ?civic }\n"
                + "}}\n"
                + "FILTER NOT EXISTS { ?s owl:sameAs ?xx}\n"
                + "} ORDER BY ?dist";
        //System.out.println(result);
        return result;
    }

    // get SPARQL query (services within distance)
    public static String getSPARQLQueryForGroupOld(ArrayList<String> ctgs, String group, double latitude, double longitude, double distance) {
        String subclasses_sparql = "";
        String macroclasses_sparql = "";
        String result = "";
        int subclasses_counter = 0;
        for (String ctg : ctgs) {
            if (!group.equalsIgnoreCase("Events")) {
                // this class is a sub class
                HashMap<String, String> tmp = categories.get(ctg);
                if (tmp != null) {
                    if (tmp.get("type").equals("subclass") && !ctg.equalsIgnoreCase("Event")) {
                        subclasses_sparql += (subclasses_counter == 0 ? "" : ",") + "km4c:" + ctg;
                        subclasses_counter++;
                    } // this class is a macro class
                    else if (tmp.get("type").equals("macroclass")) {
                        macroclasses_sparql += " UNION{?s a km4c:" + ctg + " OPTION(inference \"urn:ontology\")}";
                    }
                }
            }
        }
        if (group.equalsIgnoreCase("Events")) {
            macroclasses_sparql += "{?s a km4c:Event OPTION(inference \"urn:ontology\"). ?s <http://schema.org/startDate> ?sd. FILTER(?sd >= NOW())}";
        } else {
            subclasses_sparql = "{ ?s a ?tp FILTER(?tp IN (" + subclasses_sparql + "))}";
        }
        String optional = "OPTIONAL {{?s schema:name ?name} UNION {?s foaf:name ?name}}\n"
                + "    OPTIONAL {?s schema:streetAddress ?address}\n"
                + "    OPTIONAL {?s km4c:houseNumber ?civic }";
        result = "# RECOMMEND\n"
                + "PREFIX km4c:<http://www.disit.org/km4city/schema#>\n"
                + "PREFIX schema:<http://schema.org/>\n"
                + "SELECT DISTINCT ?s ?t ?name ?address ?civic ?dist WHERE {\n"
                + "?s a ?t.\n"
                + "FILTER(?t!=km4c:RegularService && ?t!=km4c:Service && ?t!=km4c:DigitalLocation && ?t!=km4c:TransverseService)\n"
                + subclasses_sparql
                + macroclasses_sparql
                + " {\n"
                + "   ?s km4c:hasAccess ?entry.\n"
                + "   ?entry geo:lat ?elt.\n"
                + "   ?entry geo:long ?elg.\n"
                + "   ?entry geo:geometry ?geo.\n"
                + "   FILTER(bif:st_distance(?geo, bif:st_point(" + longitude + "," + latitude + ")) <= " + distance + ")\n"
                + "   BIND(bif:st_distance(?geo, bif:st_point(" + longitude + "," + latitude + ")) AS ?dist)\n"
                + "  } UNION {\n"
                + "   ?s geo:lat ?elt.\n"
                + "   ?s geo:long ?elg.\n"
                + "   ?s geo:geometry ?geo.\n"
                + "   FILTER(bif:st_distance(?geo, bif:st_point(" + longitude + "," + latitude + ")) <= " + distance + ")\n"
                + "   BIND(bif:st_distance(?geo, bif:st_point(" + longitude + "," + latitude + ")) AS ?dist)\n"
                + "  }\n"
                + optional
                + "} ORDER BY ?dist";
        //System.out.println(result);
        return result;
    }

    // get SPARQL query (services within distance)
    public static String getSPARQLQueryForGroup(ArrayList<String> ctgs, String group, double latitude, double longitude, double distance) {
        String subclasses_sparql = "";
        String macroclasses_sparql = "";
        String result = "";
        int subclasses_counter = 0;
        if (ctgs != null) {
            for (String ctg : ctgs) {
                if (!group.equalsIgnoreCase("Events")) {
                    // this class is a sub class
                    HashMap<String, String> tmp = categories.get(ctg);
                    if (tmp != null) {
                        if (tmp.get("type").equals("subclass") && !ctg.equalsIgnoreCase("Event")) {
                            subclasses_sparql += (subclasses_counter == 0 ? "" : ",") + "km4c:" + ctg;
                            subclasses_counter++;
                        } // this class is a macro class
                        else if (tmp.get("type").equals("macroclass")) {
                            macroclasses_sparql += " UNION{?s a km4c:" + ctg + " OPTION(inference \"urn:ontology\")}";
                        }
                    }
                }
            }
        }
        if (group.equalsIgnoreCase("Events")) {
            macroclasses_sparql = "{?s a km4c:Event OPTION(inference \"urn:ontology\"). ?s <http://schema.org/startDate> ?sd. FILTER(?sd >= NOW())}";
        } else {
            subclasses_sparql = subclasses_sparql.equals("") ? "" : "{ ?s a ?tp FILTER(?tp IN (" + subclasses_sparql + "))}";
        }
        if (subclasses_sparql.equals("") && !group.equalsIgnoreCase("Events")) {
            macroclasses_sparql = macroclasses_sparql.substring(6);
        }
        result = "# RECOMMEND\n"
                + "PREFIX km4c:<http://www.disit.org/km4city/schema#>\n"
                + "PREFIX schema:<http://schema.org/>\n"
                + "SELECT DISTINCT ?s ?t ?name ?address ?civic ?dist WHERE {\n"
                + "?s a ?t.\n"
                + "FILTER(?t!=km4c:RegularService && ?t!=km4c:Service && \n"
                + "?t!=km4c:DigitalLocation && ?t!=km4c:TransverseService)\n"
                + subclasses_sparql
                + macroclasses_sparql
                + "\n"
                + "FILTER NOT EXISTS { ?s owl:sameAs ?xx}\n"
                + "OPTIONAL {\n"
                + "    ?s km4c:hasAccess ?entry.\n"
                + "    ?entry geo:geometry ?geo1.\n"
                + "    FILTER(bif:st_distance(?geo1, \n"
                + "bif:st_point(" + longitude + "," + latitude + ")) <= " + distance + ")\n"
                + "    BIND(bif:st_distance(?geo1, \n"
                + "bif:st_point(" + longitude + "," + latitude + ")) AS ?dist1)\n"
                + "   }\n"
                + "OPTIONAL {\n"
                + "    ?s geo:lat []; geo:long [].\n"
                + "    ?s geo:geometry ?geo2.\n"
                + "    FILTER(bif:st_distance(?geo2, \n"
                + "bif:st_point(" + longitude + "," + latitude + ")) <= " + distance + ")\n"
                + "    BIND(bif:st_distance(?geo2, \n"
                + "bif:st_point(" + longitude + "," + latitude + ")) AS ?dist2)\n"
                + "   }\n"
                + "filter (bound(?dist1) || bound(?dist2))\n"
                + "BIND( IF(bound(?dist1), ?dist1, ?dist2) as ?dist)\n"
                + "\n"
                + "OPTIONAL {{?s schema:name ?name} UNION {?s foaf:name ?name}}\n"
                + "     OPTIONAL {?s schema:streetAddress ?address}\n"
                + "     OPTIONAL {?s km4c:houseNumber ?civic }} ORDER BY ?dist\n";
        //System.out.println(result);
        return result;
    }

    //get SPARQL query (number of services within distance)
    public static String getNumSPARQLQuery(long userID, ArrayList<String> ctgs, double latitude, double longitude, double distance) {
        String subclasses_sparql = "";
        String macroclasses_sparql = "";
        String result;
        int subclasses_counter = 0;
        for (String ctg : ctgs) {
            HashMap<String, String> tmp = categories.get(ctg);
            if (tmp != null) {
                // this class is a sub class
                if (tmp.get("type").equals("subclass")) {
                    subclasses_sparql += (subclasses_counter == 0 ? "" : ",") + "km4c:" + ctg;
                    subclasses_counter++;
                } // this class is a macro class
                else {
                    macroclasses_sparql += "UNION{?s a km4c:" + ctg + " OPTION(inference \"urn:ontology\")}";
                }
            }
        }
        // insert bus stops and events anyway
        //macroclasses_sparql += "UNION{?s a km4c:BusStop OPTION(inference \"urn:ontology\")}";
        macroclasses_sparql += "UNION{?s a km4c:Event OPTION(inference \"urn:ontology\"). ?s <http://schema.org/startDate> ?sd. FILTER(?sd >= NOW())}";
        if (subclasses_counter == 0) {
            macroclasses_sparql = macroclasses_sparql.substring(5);
        } else {
            subclasses_sparql = "{ ?s a ?tp FILTER(?tp IN (" + subclasses_sparql + "))}";
        }
        result = "# RECOMMEND_COUNT\n"
                + "PREFIX km4c:<http://www.disit.org/km4city/schema#>\n"
                + "SELECT SUM(?services) AS ?n {\n"
                + "SELECT COUNT(DISTINCT ?s) AS ?services WHERE {\n"
                + "?s a ?t.\n"
                + "FILTER(?t!=km4c:RegularService && ?t!=km4c:Service && ?t!=km4c:DigitalLocation && ?t!=km4c:TransverseService)\n"
                + subclasses_sparql
                + macroclasses_sparql
                + " {\n"
                + "   ?s km4c:hasAccess ?entry.\n"
                + "   ?entry geo:lat ?elt.\n"
                + "   ?entry geo:long ?elg.\n"
                + "   ?entry geo:geometry ?geo.\n"
                + "   FILTER(bif:st_distance(?geo, bif:st_point(" + longitude + "," + latitude + ")) <= " + distance + ")\n"
                + "   BIND(bif:st_distance(?geo, bif:st_point(" + longitude + "," + latitude + ")) AS ?dist)\n"
                + "  } UNION {\n"
                + "   ?s geo:lat ?elt.\n"
                + "   ?s geo:long ?elg.\n"
                + "   ?s geo:geometry ?geo.\n"
                + "   FILTER(bif:st_distance(?geo, bif:st_point(" + longitude + "," + latitude + ")) <= " + distance + ")\n"
                + "   BIND(bif:st_distance(?geo, bif:st_point(" + longitude + "," + latitude + ")) AS ?dist)\n"
                + "  }\n"
                + "} ORDER BY ?dist\n"
                + "}\n";
        return result;
    }

    //get the database connection (used by other classes too)
    public static Connection getConnection() {
        try {
            if (connectionPool == null) {
                if (prop == null) {
                    prop = new Properties();
                    prop
                            .load(Recommender.class
                                    .getResourceAsStream("settings.properties"));
                }
                connectionPool = new ConnectionPool("jdbc:mysql://" + prop.getProperty("db_hostname") + "/recommender", prop.getProperty("db_username"), prop.getProperty("db_password"));
                if (dataSource == null) {
                    dataSource = connectionPool.setUp();
                }
            }
            return dataSource.getConnection();

        } catch (IOException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);

            return null;
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);

            return null;
        } catch (Exception ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);

            return null;
        }
    }

    //get the database connection (used by other classes too)
    public static Connection getConnection(String hostname, String schema, String userName, String password) {
        try {
            if (connectionPool1 == null) {
                if (prop == null) {
                    prop = new Properties();
                    prop
                            .load(Recommender.class
                                    .getResourceAsStream("settings.properties"));
                }
                connectionPool1 = new ConnectionPool("jdbc:mysql://" + hostname + "/" + schema, userName, password);
                if (dataSource1 == null) {
                    dataSource1 = connectionPool1.setUp();
                }
            }
            return dataSource1.getConnection();
        } catch (IOException e) {
            return null;

        } catch (Exception ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);

            return null;
        }
    }

    public static Properties getProperties() {
        return prop;
    }

    public static HashMap<String, String> getSettings() {
        return settings;
    }

    // load settings from MySQL database
    public static void loadSettings() {
        Connection conn = getConnection();
        if (settings == null) {
            settings = new HashMap<>();
        }
        if (profile_settings == null) {
            profile_settings = new HashMap<>();
        }
        HashMap<String, Integer> profile_settings_map = new HashMap<>();
        try {
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT * FROM recommender.settings");
            preparedStatement.executeQuery();
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                settings.put(rs.getString("name"), rs.getString("value"));
            }
            preparedStatement = conn.prepareStatement("SELECT * FROM recommender.profile_settings");
            preparedStatement.executeQuery();
            rs = preparedStatement.executeQuery();
            while (rs.next()) {
                profile_settings_map.put("max_recommendations_groups", rs.getInt("max_recommendations_groups"));
                profile_settings_map.put("max_recommendations_per_day", rs.getInt("max_recommendations_per_day"));
                profile_settings.put(rs.getString("name"), profile_settings_map);

            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
    }

    // get number of results for the SPARQL query
    public static long numresults() {
        Connection conn = null;
        long n = 0;
        try {
            String query = "PREFIX km4c:<http://www.disit.org/km4city/schema#>\n"
                    + "PREFIX otn:<http://www.pms.ifi.uni-muenchen.de/OTN#>\n"
                    + "SELECT COUNT(DISTINCT ?s) AS ?n WHERE {\n"
                    + "?s a km4c:Service option (inference \"urn:ontology\").\n"
                    + "?s a ?t.\n"
                    + "filter(?t != km4c:RegularService && ?t != km4c:Service && ?t != km4c:DigitalLocation && ?t != km4c:TransverseService && ?type != otn:StopPoint && ?type !=  geo:SpatialThing && isUri(?type))\n"
                    + "?t rdfs:subClassOf ?type\n"
                    + "{?s km4c:hasAccess ?entry.\n"
                    + "?entry geo:lat ?elat.\n"
                    + "?entry geo:long ?elong.\n"
                    + "} UNION {\n"
                    + "?s geo:lat ?elat.\n"
                    + "?s geo:long ?elong.\n"
                    + "}\n"
                    + "}";
            // perform SPARQL query
            String url = settings.get("rdf_url") + "?query=" + URLEncoder.encode(query, "UTF-8");
            /*URL u = new URL(url);
             Recommender.urlConnection = u.openConnection();
             Recommender.urlConnection.setRequestProperty("Accept", "application/sparql-results+json");
             HashMap<String, Object> res = new ObjectMapper().readValue(urlConnection.getInputStream(), HashMap.class);
             HashMap<String, Object> r = (HashMap<String, Object>) res.get("results");
             ArrayList<Object> list = (ArrayList<Object>) r.get("bindings");*/

            JSONObject results = getJSON(url);
            results = (JSONObject) results.get("results");
            JSONArray list = (JSONArray) results.get("bindings");

            for (Object obj : list) {
                //HashMap<String, Object> o = (HashMap<String, Object>) obj;
                //n = Long.parseLong((String) ((HashMap<String, Object>) o.get("n")).get("value"));
                JSONObject o = (JSONObject) obj;
                o = (JSONObject) o.get("n");
                n = (long) o.get("value");

            }
        } catch (UnsupportedEncodingException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();

                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return n;
    }

    // get number of categories for the SPARQL query
    public static int numCategories(long userID, ArrayList<String> categories, double latitude, double longitude, double distance) {
        Connection conn = null;
        int n = 0;
        try {
            // perform SPARQL query
            String url = settings.get("rdf_url") + "?query=" + URLEncoder.encode(getNumSPARQLQuery(userID, categories, latitude, longitude, distance), "UTF-8") + "&format=json";
            JSONObject results = getJSON(url);
            results = (JSONObject) results.get("results");
            JSONArray list = (JSONArray) results.get("bindings");
            for (Object obj : list) {
                JSONObject o = (JSONObject) obj;
                o = (JSONObject) o.get("n");
                n = o != null ? Integer.parseInt((String) o.get("value")) : 0;

            }
        } catch (UnsupportedEncodingException | NumberFormatException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();

                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return n;
    }

    // insert all services in MySQL database
    // TODO method to insert new services
    public static void insertServices() {
        long rows = numresults();
        Connection conn = null;
        try {
            for (int i = 0; i < rows; i += 10000) {
                String query = "PREFIX km4c:<http://www.disit.org/km4city/schema#>\n"
                        + "PREFIX otn:<http://www.pms.ifi.uni-muenchen.de/OTN#>\n"
                        + "SELECT DISTINCT ?s ?t ?type ?elat ?elong WHERE {\n"
                        + "?s a km4c:Service option (inference \"urn:ontology\").\n"
                        + "?s a ?t.\n"
                        + "filter(?t != km4c:RegularService && ?t != km4c:Service && ?t != km4c:DigitalLocation && ?t != km4c:TransverseService && ?type != otn:StopPoint && ?type !=  geo:SpatialThing && isUri(?type))\n"
                        + "?t rdfs:subClassOf ?type\n"
                        + "{?s km4c:hasAccess ?entry.\n"
                        + "?entry geo:lat ?elat.\n"
                        + "?entry geo:long ?elong.\n"
                        + "} UNION {\n"
                        + "?s geo:lat ?elat.\n"
                        + "?s geo:long ?elong.\n"
                        + "}\n"
                        + "} LIMIT 10000 OFFSET " + i;
                // perform SPARQL query
                String url = settings.get("rdf_url") + "?query=" + URLEncoder.encode(query, "UTF-8") + "&format=json";
                /*URL u = new URL(url);
                 Recommender.urlConnection = u.openConnection();
                 Recommender.urlConnection.setRequestProperty("Accept", "application/sparql-results+json");
                 HashMap<String, Object> res = new ObjectMapper().readValue(urlConnection.getInputStream(), HashMap.class);
                 HashMap<String, Object> r = (HashMap<String, Object>) res.get("results");
                 ArrayList<Object> list = (ArrayList<Object>) r.get("bindings");*/
                JSONObject results = getJSON(url);
                results = (JSONObject) results.get("results");
                JSONArray list = (JSONArray) results.get("bindings");
                conn = getConnection();
                for (Object obj : list) {
                    /*HashMap<String, Object> o = (HashMap<String, Object>) obj;
                     String s = (String) ((HashMap<String, Object>) o.get("s")).get("value");
                     String t = (String) ((HashMap<String, Object>) o.get("t")).get("value");
                     String type = (String) ((HashMap<String, Object>) o.get("type")).get("value");
                     String lat = (String) ((HashMap<String, Object>) o.get("elat")).get("value");
                     String lon = (String) ((HashMap<String, Object>) o.get("elong")).get("value");*/
                    JSONObject o = (JSONObject) obj;
                    String s = (String) ((JSONObject) o.get("s")).get("value");
                    String t = (String) ((JSONObject) o.get("t")).get("value");
                    String type = (String) ((JSONObject) o.get("type")).get("value");
                    String lat = (String) ((JSONObject) o.get("elat")).get("value");
                    String lon = (String) ((JSONObject) o.get("elong")).get("value");

                    PreparedStatement preparedStatement = conn.prepareStatement("INSERT IGNORE INTO recommender.services (service, subclass, macroclass, latitude, longitude) VALUES (?,?,?,?,?)");
                    preparedStatement.setString(1, s);
                    preparedStatement.setString(2, t);
                    preparedStatement.setString(3, type);
                    try {
                        preparedStatement.setDouble(4, Double.parseDouble(lat));
                        preparedStatement.setDouble(5, Double.parseDouble(lon));
                    } catch (NumberFormatException ex) {
                        preparedStatement.setDouble(4, 0.0);
                        preparedStatement.setDouble(5, 0.0);
                    }
                    preparedStatement.executeUpdate();

                }
            }
        } catch (UnsupportedEncodingException | SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();

                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
    }

    // insert a service in MySQL database
    public static void insertService(String serviceURI) {
        Connection conn = null;
        try {
            String query = "PREFIX km4c:<http://www.disit.org/km4city/schema#>\n"
                    + "PREFIX otn:<http://www.pms.ifi.uni-muenchen.de/OTN#>\n"
                    + "SELECT ?s ?t ?type ?elat ?elong WHERE {\n"
                    + "BIND(<" + serviceURI + "> AS ?s)\n"
                    + " ?s a ?t.\n"
                    + "filter(?t != km4c:RegularService && ?t != km4c:Service && ?t != km4c:DigitalLocation && ?t != km4c:TransverseService && ?type != otn:StopPoint && ?type !=  geo:SpatialThing && isUri(?type))\n"
                    + " ?t rdfs:subClassOf ?type\n"
                    + " {?s km4c:hasAccess ?entry.\n"
                    + " ?entry geo:lat ?elat.\n"
                    + " ?entry geo:long ?elong.\n"
                    + " } UNION {\n"
                    + "   ?s geo:lat ?elat.\n"
                    + "   ?s geo:long ?elong.\n"
                    + "  }\n"
                    + "} LIMIT 1";
            // perform SPARQL query
            String url = settings.get("rdf_url") + "?query=" + URLEncoder.encode(query, "UTF-8") + "&format=json";
            /*URL u = new URL(url);
             Recommender.urlConnection = u.openConnection();
             Recommender.urlConnection.setRequestProperty("Accept", "application/sparql-results+json");
             HashMap<String, Object> res = new ObjectMapper().readValue(urlConnection.getInputStream(), HashMap.class);
             HashMap<String, Object> r = (HashMap<String, Object>) res.get("results");
             ArrayList<Object> list = (ArrayList<Object>) r.get("bindings");*/
            JSONObject results = getJSON(url);
            results = (JSONObject) results.get("results");
            JSONArray list = (JSONArray) results.get("bindings");
            conn = getConnection();
            for (Object obj : list) {
                /*HashMap<String, Object> o = (HashMap<String, Object>) obj;
                 String s = (String) ((HashMap<String, Object>) o.get("s")).get("value");
                 String t = (String) ((HashMap<String, Object>) o.get("t")).get("value");
                 String type = (String) ((HashMap<String, Object>) o.get("type")).get("value");
                 String lat = (String) ((HashMap<String, Object>) o.get("elat")).get("value");
                 String lon = (String) ((HashMap<String, Object>) o.get("elong")).get("value");*/
                JSONObject o = (JSONObject) obj;
                String s = (String) ((JSONObject) o.get("s")).get("value");
                String t = (String) ((JSONObject) o.get("t")).get("value");
                String type = (String) ((JSONObject) o.get("type")).get("value");
                String lat = (String) ((JSONObject) o.get("elat")).get("value");
                String lon = (String) ((JSONObject) o.get("elong")).get("value");

                PreparedStatement preparedStatement = conn.prepareStatement("INSERT IGNORE INTO recommender.services (service, subclass, macroclass, latitude, longitude) VALUES (?,?,?,?,?)");
                preparedStatement.setString(1, s);
                preparedStatement.setString(2, t);
                preparedStatement.setString(3, type);
                preparedStatement.setDouble(4, Double.parseDouble(lat));
                preparedStatement.setDouble(5, Double.parseDouble(lon));
                preparedStatement.executeUpdate();

            }
        } catch (UnsupportedEncodingException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } catch (SQLException | NumberFormatException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();

                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
    }

    // get services' ids that are NOT to be excluded from the results
    public static FastIDSet getServicesIDs(long userID, double latitude, double longitude) {
        return null;
    }

    // insert preference into MySQL database and update recommender
    public static void insertPreference(long userID, long itemID, float preference, double latitude, double longitude) {
        Connection conn = null;
        try {
            PreferenceArray preferenceArray = dm.getPreferencesFromUser(userID);
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("INSERT IGNORE INTO recommender.preferences (user_id, item_id, preference, latitude, longitude) VALUES (?,?,?,?,?)");
            preparedStatement.setLong(1, userID);
            preparedStatement.setLong(2, itemID);
            preparedStatement.setDouble(3, preference);
            preparedStatement.setDouble(4, latitude);
            preparedStatement.setDouble(5, longitude);
            preparedStatement.executeUpdate();

            // TODO: refresh the recommender
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } catch (TasteException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();

                }
            } catch (SQLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
    }

    // calculate distance in km between coordinates in decimal degrees (latitude, longitude)
    public static double distFrom(double lat1, double lng1, double lat2, double lng2) {
        double earthRadius = 6371000; //meters
        double dLat = Math.toRadians(lat2 - lat1);
        double dLng = Math.toRadians(lng2 - lng1);
        double a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
                + Math.cos(Math.toRadians(lat1)) * Math.cos(Math.toRadians(lat2))
                * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        double c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        double dist = (double) (earthRadius * c);

        return dist / 1000;
    }

    public static JSONObject getJSON(String json_url) {
        Object o;
        try {
            URL url = new URL(json_url);
            URLConnection connection = url.openConnection();
            // enable caching
            connection.setUseCaches(true);
            //BufferedReader in = new BufferedReader(new InputStreamReader(url.openStream(), "UTF-8"));
            BufferedReader in = new BufferedReader(new InputStreamReader(connection.getInputStream(), "UTF-8"));
            JSONParser p = new JSONParser();
            o = p.parse(in);
            in.close();

        } catch (MalformedURLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);

            return null;
        } catch (UnsupportedEncodingException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);

            return null;
        } catch (IOException | ParseException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);

            return null;
        }
        return (JSONObject) o;
    }

    private static JSONObject postRequest(String url, String parameters) {
        Object o = null;
        try {
            URL obj = new URL(url);
            URLConnection connection = new URL(url).openConnection();
            connection.setDoOutput(true); // Triggers POST.
            connection.setRequestProperty("Accept-Charset", "UTF-8");
            connection.setRequestProperty("Accept", "application/sparql-results+json");
            connection.setRequestProperty("Content-Type", "application/x-www-form-urlencoded;charset=UTF-8");
            try (OutputStream output = connection.getOutputStream()) {
                output.write(parameters.getBytes("UTF-8"));
            }
            BufferedReader in = new BufferedReader(new InputStreamReader(connection.getInputStream(), "UTF-8"));
            JSONParser p = new JSONParser();
            /*String s = null;
             while ((s = in.readLine()) != null) {
             System.out.println(s);
             }*/
            o = p.parse(in);
            in.close();
        } catch (MalformedURLException ex) {
            Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
            return null;
        } catch (IOException ex) {
            Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
            return null;
        } catch (ParseException ex) {
            Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
        }
        return (JSONObject) o;
    }

    private static int getRowCount(ResultSet resultSet) {
        if (resultSet == null) {
            return 0;
        }
        try {
            resultSet.last();
            return resultSet.getRow();
        } catch (SQLException e) {
        } finally {
            try {
                resultSet.beforeFirst();
            } catch (SQLException e) {
            }
        }
        return 0;
    }

    // read an URL
    public static String readURL(String url) {
        String result = "";
        try {
            URL u = new URL(url);
            try (BufferedReader in = new BufferedReader(
                    new InputStreamReader(u.openStream()))) {
                String inputLine;
                while ((inputLine = in.readLine()) != null) {
                    result += inputLine;

                }
            } catch (IOException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }

        } catch (MalformedURLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
        return result;
    }

    // populate MySQL table recommender.categories with macro classes and sub classes from JSON
    public static void insertCategories() {
        Connection conn = null;
        PreparedStatement preparedStatement;
        PreparedStatement preparedStatement_children;
        try {
            conn = getConnection();
            String json = readURL(settings.get("map_search_menu_all_url"));
            JSONParser jsonParser = new JSONParser();
            JSONArray json_array = (JSONArray) jsonParser.parse(json);
            for (Object json_object : json_array) {
                JSONObject obj = (JSONObject) json_object;
                Iterator<String> keys = obj.keySet().iterator();
                preparedStatement = conn.prepareStatement("REPLACE INTO recommender.categories (`key`, title, folder, icon) VALUES(?,?,?,?)");
                while (keys.hasNext()) {
                    String key = keys.next();
                    // set macroclass insert fields
                    if (!key.equals("children")) {
                        //System.out.println("key: " + key + " value: " + (String) obj.get(key));
                        switch (key) {
                            case "key":
                                preparedStatement.setString(1, (String) obj.get(key));
                                break;
                            case "title":
                                preparedStatement.setString(2, (String) obj.get(key));
                                break;
                            case "folder":
                                preparedStatement.setString(3, (String) obj.get(key));
                                break;
                            case "icon":
                                preparedStatement.setString(4, (String) obj.get(key));
                                break;
                        }
                    } //set subclass insert fields
                    else {
                        JSONArray json_children_array = (JSONArray) obj.get(key);
                        preparedStatement_children = conn.prepareStatement("REPLACE INTO recommender.categories (`key`, title, icon, macroclass) VALUES(?,?,?,?)");
                        for (Object json_children_object : json_children_array) {
                            JSONObject children_obj = (JSONObject) json_children_object;
                            Iterator<String> k = children_obj.keySet().iterator();
                            while (k.hasNext()) {
                                String k1 = k.next();
                                //System.out.println("key: " + k1 + " value: " + (String) children_obj.get(k1));
                                switch (k1) {
                                    case "key":
                                        preparedStatement_children.setString(1, (String) children_obj.get(k1));
                                        break;
                                    case "title":
                                        preparedStatement_children.setString(2, (String) children_obj.get(k1));
                                        break;
                                    case "icon":
                                        preparedStatement_children.setString(3, (String) children_obj.get(k1));
                                        break;
                                }
                            }
                            // set other subclass insert fields (macroclass and type)
                            preparedStatement_children.setString(4, settings.get("rdf_schema_prefix_url") + obj.get("key"));
                            //preparedStatement_children.setString(5, "subclass");
                            preparedStatement_children.executeUpdate();
                        }
                    }
                }
                // set other macroclass insert field (type)
                //preparedStatement.setString(5, "macroclass");
                preparedStatement.executeUpdate();
            }
            // insert categories not present in the JSON
            // Event
            preparedStatement = conn.prepareStatement("REPLACE INTO recommender.categories (`key`) VALUES(?)");
            preparedStatement.setString(1, "Event");
            preparedStatement.executeUpdate();
            // Bus Stop
            preparedStatement = conn.prepareStatement("REPLACE INTO recommender.categories (`key`) VALUES(?)");
            preparedStatement.setString(1, "BusStop");
            preparedStatement.executeUpdate();
            // Fresh Place
            /*preparedStatement = conn.prepareStatement("REPLACE INTO recommender.categories (`key`) VALUES(?)");
             preparedStatement.setString(1, "Fresh_place");
             preparedStatement.executeUpdate();*/
            // Digital Location
            /*preparedStatement = conn.prepareStatement("REPLACE INTO recommender.categories (`key`) VALUES(?)");
             preparedStatement.setString(1, "DigitalLocation");
             preparedStatement.executeUpdate();*/

        } catch (ParseException | SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
    }

    // load user profiles (e.g., all, citizen, commuter, student, tourist), loadSettings() before invoking this method
    public static void loadUsersProfiles() {
        users_profiles = new HashMap<>();
        String[] profiles_urls = new String[]{settings.get("map_search_menu_all_url"),
            settings.get("map_search_menu_citizen_url"),
            settings.get("map_search_menu_commuter_url"),
            settings.get("map_search_menu_student_url"),
            settings.get("map_search_menu_tourist_url"),
            settings.get("map_search_menu_disabled_url"),
            settings.get("map_search_menu_operator_url")};
        try {
            for (String profile_url : profiles_urls) {
                String json = readURL(profile_url);
                JSONParser jsonParser = new JSONParser();
                JSONArray json_array = (JSONArray) jsonParser.parse(json);
                ArrayList<String> tmp = new ArrayList<>();
                for (Object json_object : json_array) {
                    JSONObject obj = (JSONObject) json_object;
                    Iterator<String> keys = obj.keySet().iterator();
                    while (keys.hasNext()) {
                        String key = keys.next();
                        // if the json is "all"
                        if (profile_url.contains("all")) {
                            if (key.equals("children")) {
                                JSONArray json_children_array = (JSONArray) obj.get(key);
                                for (Object json_children_object : json_children_array) {
                                    JSONObject children_obj = (JSONObject) json_children_object;
                                    Iterator<String> k = children_obj.keySet().iterator();
                                    while (k.hasNext()) {
                                        String k1 = k.next();
                                        if (k1.equals("key")) {
                                            tmp.add((String) children_obj.get(k1));
                                        }
                                    }
                                }
                            }
                        } // if the json is "citizen" or "commuter" or "student" or "tourist" or "disabled" or "operator"
                        else {
                            if (key.equals("key")) {
                                tmp.add((String) obj.get(key));
                            }
                        }
                    }
                }
                // add mandatory categories for each profile
                // add event
                //tmp.clear();
                tmp.add("Event");

                if (profile_url.contains("all")) {
                    users_profiles.put("all", tmp);
                } else if (profile_url.contains("citizen")) {
                    users_profiles.put("citizen", tmp);
                } else if (profile_url.contains("commuter")) {
                    users_profiles.put("commuter", tmp);
                } else if (profile_url.contains("student")) {
                    users_profiles.put("student", tmp);
                } else if (profile_url.contains("tourist")) {
                    users_profiles.put("tourist", tmp);
                } else if (profile_url.contains("disabled")) {
                    users_profiles.put("disabled", tmp);
                } else if (profile_url.contains("operator")) {
                    users_profiles.put("operator", tmp);
                }
            }
        } catch (ParseException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
    }

    // add a preference (userID, itemID, value) to the Data Model
    // if a preference already exists, increment the value by 2
    // NOT USED, ITEMIDS ARE SUBCLASSES OR MACROCLASSES, NOT SERVICEURIS
    /*public static String like(String user, String serviceURI) {
     try {
     long userID = getUserID(user);
     long itemID = getServiceID(serviceURI);
     dm.setPreference(userID, itemID, 2);
     } catch (TasteException ex) {
     Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
     return "{\"Result\":\"false\"}";
     }
     return "{\"Result\":\"true\"}";
     }*/
    // remove a preference (userID, itemID) from the Data Model
    // updated: set a preference (userID, itemID to 0 in the Data Model
    // NOT USED, ITEMIDS ARE SUBCLASSES OR MACROCLASSES, NOT SERVICEURIS
    /*public static String dislike(String user, String serviceURI) {
     try {
     long userID = getUserID(user);
     long itemID = getServiceID(serviceURI);
     //dm.removePreference(userID, itemID);
     dm.setPreference(userID, itemID, 0);
     } catch (TasteException ex) {
     Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
     return "{\"Result\":\"false\"}";
     }
     return "{\"Result\":\"true\"}";
     }*/
    // dislike a group for an user
    public static String dislike(String user, String group) {
        Connection conn = null;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement;
            //preparedStatement = conn.prepareStatement("REPLACE INTO recommender.dislike (user, dislikedGroup) VALUES(?, ?)");
            preparedStatement = conn.prepareStatement("INSERT INTO recommender.dislike (user, dislikedGroup) VALUES(?, ?) ON DUPLICATE KEY UPDATE dislikedGroup = VALUES(dislikedGroup)");
            preparedStatement.setString(1, user);
            preparedStatement.setString(2, group);
            preparedStatement.executeUpdate();

        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);

            return "false";
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
        return "true";
    }

    // dislike a subclass for an user
    public static String dislikeSubclass(String user, String subclass) {
        Connection conn = null;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement;
            preparedStatement = conn.prepareStatement("INSERT INTO recommender.dislike (user, dislikedSubclass) VALUES(?, ?) ON DUPLICATE KEY UPDATE dislikedSubclass = VALUES(dislikedSubclass)");
            preparedStatement.setString(1, user);
            preparedStatement.setString(2, subclass);
            preparedStatement.executeUpdate();

        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);

            return "false";
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
        return "true";
    }

    // remove the dislike of all groups for an user
    public static String removeDislike(String user) {
        Connection conn = null;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement;
            preparedStatement = conn.prepareStatement("DELETE FROM recommender.dislike WHERE user = ?");
            preparedStatement.setString(1, user);
            preparedStatement.executeUpdate();

        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);

            return "false";
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
        return "true";
    }

    // get current time (hh:mm:ss)
    public static String getCurrentTime() {
        Calendar cal = Calendar.getInstance();
        SimpleDateFormat sdf = new SimpleDateFormat("HH:mm:ss");
        return sdf.format(cal.getTime());
    }

    // check if the service is open at the current time
    public static boolean isMacroclassOpen(String macroclass) {
        try {
            Calendar cal = Calendar.getInstance();
            SimpleDateFormat sdf = new SimpleDateFormat("HH:mm:ss");
            Date current = sdf.parse(sdf.format(cal.getTime()));

            //get opening and closing times for the service
            HashMap<String, String> tmp = macroclass_hours.get(macroclass);
            Date opening1 = sdf.parse(tmp.get("opening1"));
            Date closing1 = sdf.parse(tmp.get("closing1"));
            Date opening2 = null;
            Date closing2 = null;
            String opening2_string = tmp.get("opening2");
            if (opening2_string != null) {
                opening2 = sdf.parse(opening2_string);
            }
            String closing2_string = tmp.get("closing2");
            if (closing2_string != null) {
                closing2 = sdf.parse(closing2_string);
            }

            if (opening2 != null && closing2 != null) {
                if ((current.compareTo(opening1) >= 0
                        && current.compareTo(closing1) <= 0)
                        || (current.compareTo(opening2) >= 0
                        && current.compareTo(closing2) <= 0)) {
                    return true;
                }
            } else {
                if (current.compareTo(opening1) >= 0
                        && current.compareTo(closing1) <= 0) {
                    return true;

                }
            }
        } catch (java.text.ParseException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
        return false;
    }

    // get the group of a subclass
    public static String getSubclassGroup(String subclass) {
        HashMap<String, String> t = categories.get(subclass.substring(subclass.lastIndexOf("#") + 1));
        if (t != null) {
            return t.get("group");
        }
        return null;
    }

    // load groups with priorities (the lower the highest)
    public static void loadGroups() {
        Connection conn = null;
        if (groups_all == null) {
            groups_all = new HashMap<>();
        }
        if (groups_student == null) {
            groups_student = new HashMap<>();
        }
        if (groups_commuter == null) {
            groups_commuter = new HashMap<>();
        }
        if (groups_citizen == null) {
            groups_citizen = new HashMap<>();
        }
        if (groups_tourist == null) {
            groups_tourist = new HashMap<>();
        }
        if (groups_disabled == null) {
            groups_disabled = new HashMap<>();
        }
        if (groups_operator == null) {
            groups_operator = new HashMap<>();
        }
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT `group`, `all` FROM recommender.groups");
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                groups_all.put(rs.getInt("all"), rs.getString("group"));
            }
            preparedStatement = conn.prepareStatement("SELECT `group`, `student` FROM recommender.groups");
            rs = preparedStatement.executeQuery();
            while (rs.next()) {
                groups_student.put(rs.getInt("student"), rs.getString("group"));
            }
            preparedStatement = conn.prepareStatement("SELECT `group`, `commuter` FROM recommender.groups");
            rs = preparedStatement.executeQuery();
            while (rs.next()) {
                groups_commuter.put(rs.getInt("commuter"), rs.getString("group"));
            }
            preparedStatement = conn.prepareStatement("SELECT `group`, `citizen` FROM recommender.groups");
            rs = preparedStatement.executeQuery();
            while (rs.next()) {
                groups_citizen.put(rs.getInt("citizen"), rs.getString("group"));
            }
            preparedStatement = conn.prepareStatement("SELECT `group`, tourist FROM recommender.groups");
            rs = preparedStatement.executeQuery();
            while (rs.next()) {
                groups_tourist.put(rs.getInt("tourist"), rs.getString("group"));

            }
            preparedStatement = conn.prepareStatement("SELECT `group`, `disabled` FROM recommender.groups");
            rs = preparedStatement.executeQuery();
            while (rs.next()) {
                groups_disabled.put(rs.getInt("disabled"), rs.getString("group"));
            }
            preparedStatement = conn.prepareStatement("SELECT `group`, `operator` FROM recommender.groups");
            rs = preparedStatement.executeQuery();
            while (rs.next()) {
                groups_operator.put(rs.getInt("operator"), rs.getString("group"));
            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
    }

    // load groups with their translations
    public static void loadGroupsLangs() {
        Connection conn = null;
        if (groups_langs == null) {
            groups_langs = new HashMap<>();
        }
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT * FROM recommender.groups_lang");
            ResultSet rs = preparedStatement.executeQuery();
            ResultSetMetaData rsmd = rs.getMetaData();
            int column_counter = rsmd.getColumnCount();
            while (rs.next()) {
                for (int i = 1; i <= column_counter; i++) {
                    if (!rsmd.getColumnName(i).equals("en") && !rsmd.getColumnName(i).equals("id")) {
                        if (groups_langs.get(rs.getString("en")) != null) {
                            HashMap<String, String> tmp = (HashMap<String, String>) groups_langs.get(rs.getString("en"));
                            tmp.put(rsmd.getColumnName(i), rs.getString(i));
                            groups_langs.put(rs.getString("en"), tmp);
                        } else {
                            HashMap<String, String> tmp = new HashMap<>();
                            tmp.put(rsmd.getColumnName(i), rs.getString(i));
                            groups_langs.put(rs.getString("en"), tmp);

                        }
                    }
                }
            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
    }

    // get the group for a profile
    public static HashMap<Integer, String> getGroup(String profile) {
        switch (profile) {
            case "all":
                return groups_all;
            case "student":
                return groups_student;
            case "commuter":
                return groups_commuter;
            case "citizen":
                return groups_citizen;
            case "tourist":
                return groups_tourist;
            case "disabled":
                return groups_disabled;
            case "operator":
                return groups_operator;
            default:
                return groups_all;
        }
    }

    // get weather from coordinates
    public static JSONObject getWeather(double latitude, double longitude) {
        JSONObject obj = null;
        Date date = new Date();
        Format formatter = new SimpleDateFormat("yyyy-MM-dd-HH");
        String timestamp = formatter.format(date);
        try {
            obj = getJSON(settings.get("weather_uri") + "?position=" + latitude + ";" + longitude);
            if (obj != null && obj.get("municipalityUri") != null) {
                String url = settings.get("service_map_url") + "?serviceUri=" + obj.get("municipalityUri") + "&format=json&timestamp=" + timestamp; // use timestamp to not cache this url by squid
                obj = getJSON(url);
            }

        } catch (Exception ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
        return obj;
    }

    // get tweets (tweets table is populated by another process running on the scheduler)
    public static JSONObject getTweets(String user, String profile, String group, long assessor) {
        Connection conn = null;
        JSONObject obj = new JSONObject();
        try {
            conn = getConnection();
            ArrayList<String> already_recommended_tweets = getAlreadyRecommendedTweets(conn, user);
            PreparedStatement preparedStatement1 = conn.prepareStatement("SELECT description, name, tweets FROM recommender.tweets a LEFT JOIN recommender.groups_settings b ON a.category = b.name WHERE profile = ? AND description = ?");
            PreparedStatement preparedStatement2 = conn.prepareStatement("INSERT INTO recommender.recommendations_tweets (user, twitterId, `group`, timestamp) VALUES(?, ?, ?, CURRENT_TIMESTAMP)");
            PreparedStatement preparedStatement3 = conn.prepareStatement("DELETE FROM recommender.recommendations_tweets WHERE user = ?");
            preparedStatement1.setString(1, profile);
            preparedStatement1.setString(2, group);
            preparedStatement1.executeQuery();
            ResultSet rs = preparedStatement1.executeQuery();
            JSONParser parser = new JSONParser();
            while (rs.next()) {
                Object o = parser.parse(rs.getString("tweets"));
                JSONArray j = (JSONArray) o;
                JSONArray json_array = new JSONArray();
                int counter = 0;
                for (Object json_object : j) {
                    if (counter == 3) {
                        break;
                    }
                    JSONObject tmp = new JSONObject();
                    JSONObject jsonObject = (JSONObject) json_object;
                    // if this tweet has not been already recommended to the user
                    if (!already_recommended_tweets.contains(jsonObject.get("twitterId"))) {
                        tmp.put("Tweet", jsonObject);
                        json_array.add(tmp);
                        counter++;
                        // log recommended tweet in database
                        preparedStatement2.setString(1, user);
                        preparedStatement2.setString(2, (String) jsonObject.get("twitterId"));
                        preparedStatement2.setString(3, group);
                        preparedStatement2.executeUpdate();
                    }
                }
                // if there are results build the json
                if (json_array.size() > 0) {
                    obj.put("suggestions", json_array);
                    obj.put("label", rs.getString("name"));
                    obj.put("priority", getPriorityForGroup(profile, rs.getString("description")));
                    obj.put("group", rs.getString("description"));
                    if (assessor == 1) {
                        obj.put("assessor", 1);
                    }
                } // else clear the MySQL recommended tweets table for this user and get the tweets
                else {
                    preparedStatement3.setString(1, user);
                    preparedStatement3.executeUpdate();
                    obj = getTweets(user, profile, group, assessor);
                }
            }
        } catch (SQLException | ParseException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();
                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
        return obj;
    }

    public static ArrayList<String> getAlreadyRecommendedTweets(Connection conn, String user) {
        ArrayList<String> already_recommended_tweets = new ArrayList<>();
        try {
            PreparedStatement preparedStatement;
            preparedStatement = conn.prepareStatement("SELECT twitterId FROM recommender.recommendations_tweets WHERE user = ?");
            preparedStatement.setString(1, user);
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                already_recommended_tweets.add(rs.getString("twitterId"));
            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
        }
        return already_recommended_tweets;
    }

    public static String logViewedTweet(String user, String twitterId, String group) {
        Connection conn = null;
        boolean result = true;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement = conn.prepareStatement("INSERT INTO recommender.tweets_log (user, twitterId, `group`) VALUES (?,?,?)");
            preparedStatement.setString(1, user);
            preparedStatement.setString(2, twitterId);
            preparedStatement.setString(3, group);
            preparedStatement.executeUpdate();
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
            result = false;
        } finally {
            if (conn != null) {
                try {
                    conn.close();
                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
        return result ? "true" : "false";
    }

    // check an item, if it is unknown for the user, if yes then insert it in the recommendations table with score = 0
    public static void checkItem(long userID, long itemID) {
        try {
            Float preference_value = dm.getPreferenceValue(userID, itemID);
            if (preference_value == null) {
                dm.setPreference(userID, itemID, 0);

            }
        } catch (TasteException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
    }

    // set user preferences
    public static synchronized void setUserPreferences(String user, String profile, String userAgent) {
        Connection conn = null;
        HashMap<String, Integer> subclasses_map = new HashMap<>(); // subclasses map (key = subclass, value = count)
        HashMap<String, Integer> macroclasses_map = new HashMap<>(); // macroclasses map (key = macroclass, value = count)
        try {
            conn = getConnection();

            // delete old user preference scores
            PreparedStatement preparedStatement = conn.prepareStatement("DELETE FROM recommender.preferences WHERE user_id = ?");
            preparedStatement.setLong(1, getUserID(user, profile, userAgent)); // set userAgent too
            preparedStatement.executeUpdate();

            // get categories for profile
            ArrayList<String> profile_categories = users_profiles.get(profile);
            // insert default profile preferences scores for the user
            long userId = getUserID(user, profile, null);
            for (String category : profile_categories) {
                preparedStatement = conn.prepareStatement("REPLACE INTO recommender.preferences (user_id, item_id, preference) VALUES(?,?,?)");
                preparedStatement.setLong(1, userId);
                preparedStatement.setLong(2, (long) categories_ids.get(category));
                preparedStatement.setFloat(3, 1);
                preparedStatement.executeUpdate();
            }

            // close connection to recommender database
            conn.close();

            // open connection to AccessLog database
            conn = getConnection(prop.getProperty("db_access_log_hostname"),
                    prop.getProperty("db_access_log_schema"),
                    prop.getProperty("db_access_log_username"),
                    prop.getProperty("db_access_log_password"));

            // calculate preference scores for the user
            // increment score for queries
            preparedStatement = conn.prepareStatement("SELECT categories FROM ServiceMap.AccessLog WHERE uid = '" + user + "' AND categories IS NOT NULL AND categories != '' AND mode = 'api-services-by-gps' ORDER BY id DESC LIMIT " + settings.get("get_last_x_user_queries"));
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                String[] categories_array = rs.getString("categories").split(";");
                // if the subclasses array does contain "Service" (i.e., all subclasses), then use all of them insted of using categories_array
                if (Arrays.asList(categories_array).contains("Service")) {
                    Iterator it = categories.keySet().iterator();
                    while (it.hasNext()) {
                        String ctg = (String) it.next();
                        HashMap<String, String> t = categories.get(ctg);
                        if (t.get("type").equals("subclass")) {
                            // increment subclass score
                            if (subclasses_map.get(ctg) != null) {
                                subclasses_map.put(ctg, subclasses_map.get(ctg) + 1);
                            } else {
                                subclasses_map.put(ctg, 1);
                            }
                        } else {
                            // increment macroclass score
                            //String macroclass = t.get("macroclass");
                            if (macroclasses_map.get(ctg) != null) {
                                macroclasses_map.put(ctg, macroclasses_map.get(ctg) + 1);
                            } else {
                                macroclasses_map.put(ctg, 1);
                            }
                        }
                    }
                } // if the subclasses array does not contain "Services" (i.e., all subclasses), then use categories_array
                else {
                    for (String c : categories_array) {
                        // increment subclass score
                        if (subclasses_map.get(c) != null) {
                            subclasses_map.put(c, subclasses_map.get(c) + 1);
                        } else {
                            subclasses_map.put(c, 1);
                        }
                        // increment macroclass score
                        HashMap<String, String> tmp = categories.get(c);
                        if (tmp != null) {
                            String macroclass = tmp.get("macroclass");
                            if (macroclasses_map.get(macroclass) != null) {
                                macroclasses_map.put(macroclass, macroclasses_map.get(macroclass) + 1);
                            } else {
                                macroclasses_map.put(macroclass, 1);
                            }
                        }
                    }
                }
            }
            // increment score for views
            preparedStatement = conn.prepareStatement("SELECT categories FROM ServiceMap.AccessLog WHERE uid = ? AND categories IS NOT NULL AND serviceURI LIKE 'http%' AND mode = ? ORDER BY id DESC LIMIT ?");
            preparedStatement.setString(1, user);
            preparedStatement.setString(2, "api-service-info");
            preparedStatement.setInt(3, Integer.parseInt(settings.get("get_last_x_user_queries")));
            rs = preparedStatement.executeQuery();
            while (rs.next()) {
                String[] macroclass_subclass = rs.getString("categories").split(";");
                // increment subclass score
                if (subclasses_map.get(macroclass_subclass[1]) != null) {
                    subclasses_map.put(macroclass_subclass[1], subclasses_map.get(macroclass_subclass[1]) + 5);
                } else {
                    subclasses_map.put(macroclass_subclass[1], 5);
                }
                // increment macroclass score
                if (macroclasses_map.get(macroclass_subclass[0]) != null) {
                    macroclasses_map.put(macroclass_subclass[0], macroclasses_map.get(macroclass_subclass[0]) + 5);
                } else {
                    macroclasses_map.put(macroclass_subclass[0], 5);
                }
            }
            // close connection to AccessLog database
            conn.close();

            // open connection to recommender database
            conn = getConnection();

            // insert preference scores for the user
            Iterator it = subclasses_map.keySet().iterator();
            while (it.hasNext()) {
                String key = (String) it.next();
                if (categories_ids.get(key) != null) {
                    preparedStatement = conn.prepareStatement("REPLACE INTO recommender.preferences (user_id, item_id, preference) VALUES(?,?,?)");
                    // calculate preference score = alpha * SC + beta * MC (SC = subclass score, MC = macroclass score)
                    HashMap<String, String> tmp = categories.get(key);
                    String macroclass = tmp.get("macroclass");
                    // beta * macroclass is included only if there is a macroclass (e.g., Events do not have a macroclass)
                    // tune the preference based on the userID (users are divided in three groups using module)
                    float alpha = Float.parseFloat(settings.get("alpha")) / (userId % 3 + 1);
                    float beta = Float.parseFloat(settings.get("beta")) * (userId % 3 + 1);
                    float preference = alpha * subclasses_map.get(key) + (macroclasses_map.get(macroclass) != null ? beta * macroclasses_map.get(macroclass) : 0);
                    preparedStatement.setLong(1, getUserID(user, profile, null));
                    preparedStatement.setLong(2, (long) categories_ids.get(key));
                    preparedStatement.setFloat(3, preference);
                    preparedStatement.executeUpdate();

                }
            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
    }

    // load recommender with data from city sensors
    public static void loadRecommender() {
        long size;
        long limit = 100000;
        long offset = 0;
        long time;
        long counter = 0;
        String[] profiles = new String[]{"student", "commuter", "tourist", "citizen", "all", "disabled", "operator"};
        try {
            time = System.currentTimeMillis();
            String date = "";
            do {
                String json_url = "http://www.disit.org/sensor/api_select.php?action=get_sensors&type=json&limit=" + limit + "&offset=" + offset;
                URL url = new URL(json_url);
                BufferedReader in = new BufferedReader(new InputStreamReader(url.openStream(), "UTF-8"));
                JSONParser p = new JSONParser();
                Object o = p.parse(in);
                JSONArray json_array = (JSONArray) o;
                json_array = getSortedList(json_array);
                size = json_array.size();
                offset += limit;
                for (Object json : json_array) {
                    JSONObject obj = (JSONObject) json;
                    String user_profile = profiles[(int) (Long.parseLong(((String) obj.get("sender_IP")).replace(".", "")) % 5)];
                    //System.out.println("recommending to user: " + (String) obj.get("MAC_address"));
                    if (!date.equals((String) obj.get("date"))) {
                        recommend((String) obj.get("sender_IP"), user_profile, "en", "gps", Double.parseDouble((String) obj.get("latitude")), Double.parseDouble((String) obj.get("longitude")), 1, "1.8.0.", "", "true", "true", "false", "appID", "uid2", (String) obj.get("date"));
                        counter++;
                        if (counter % 100 == 0) {
                            long tmp = System.currentTimeMillis();
                            System.out.println("Recommended to 100 users in " + ((tmp - time) / 1000) + " s");
                            time = tmp;
                        }
                    }
                    date = (String) obj.get("date");
                }
            } while (size > 0);

        } catch (IOException | ParseException | JSONException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
    }

    // sort a json array by date
    public static JSONArray getSortedList(JSONArray array) throws JSONException {
        List<JSONObject> list = new ArrayList<>();
        JSONArray result = new JSONArray();
        for (int i = 0; i < array.size(); i++) {
            list.add((JSONObject) array.get(i));
        }
        Collections.sort(list, new SortBasedOnDate());
        for (JSONObject obj : list) {
            result.add(obj);
        }
        return result;
    }

    // insert macroclass and subclass in the categories field of the AccessLog table, to be used one time only
    public static void insertMacroclassSubclassIntoAccessLog() {
        try {
            Connection conn = getConnection();
            Connection conn2 = getConnection(prop.getProperty("db_access_log_hostname"),
                    prop.getProperty("db_access_log_schema"),
                    prop.getProperty("db_access_log_username"),
                    prop.getProperty("db_access_log_password"));
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT service, macroclass, subclass FROM recommender.services");
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                PreparedStatement preparedStatement2 = conn2.prepareStatement("UPDATE ServiceMap.AccessLog SET categories = ? WHERE serviceUri = ? AND mode = ?");
                String macroclass = rs.getString("macroclass").substring(rs.getString("macroclass").indexOf("#") + 1);
                String subclass = rs.getString("subclass").substring(rs.getString("subclass").indexOf("#") + 1);
                if (macroclass.equals("http://schema.org/Event")) {
                    macroclass = "Service";
                }
                preparedStatement2.setString(1, macroclass + ";" + subclass);
                preparedStatement2.setString(2, rs.getString("service"));
                preparedStatement2.setString(3, "api-service-info");
                preparedStatement2.executeUpdate();
            }
            // close connections
            conn.close();
            conn2.close();

        } catch (SQLException ex) {
            Logger.getLogger(SortBasedOnDate.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
    }

    // Jena SPARQL 1.1 query implementation (does not work with OPTION Virtuoso syntax)
    /*public static void query(String user, double latitude, double longitude, double distance) {
     String service = settings.get("rdf_url");
     ArrayList<String> recommendations_list = svdRecommend(user, latitude, longitude, distance);
     long userID = getUserID(user);
     // perform SPARQL query
     String query = getSPARQLQuery(userID, recommendations_list, latitude, longitude, distance);
     System.out.println(query);
     QueryExecution qe = QueryExecutionFactory.sparqlService(service, query);
     try {
     org.apache.jena.query.ResultSet results = qe.execSelect();
     while (results.hasNext()) {
     QuerySolution soln = results.nextSolution();
     RDFNode x = soln.get("s");
     RDFNode r = soln.get("t");
     RDFNode l = soln.get("dist");
     System.out.println(x.toString());
     }
     } catch (Exception e) {
     System.out.println("Query error:" + e);
     } finally {
     qe.close();
     }
     }*/
    // make recommendations to users who followed paths
    public static void recommendPaths() {
        String path = "C:\\Users\\cenni\\Downloads\\paths\\";
        String[] profiles = new String[]{"student", "commuter", "tourist", "citizen", "all", "disabled", "operator"};
        Date date = new Date();
        for (int i = 1; i <= 7; i++) {
            String sha256 = org.apache.commons.codec.digest.DigestUtils.sha256Hex(i + "");
            try (BufferedReader br = new BufferedReader(new FileReader(new File(path + i + ".txt")))) {
                String line;
                int counter = 0;
                while ((line = br.readLine()) != null) {
                    String[] coordinates = line.split(",");
                    if (counter % 10 == 0) {
                        // format date
                        Format formatter = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
                        String timestamp = formatter.format(date);
                        // recommend with timestamp to log data to recommendations_log table
                        recommend(sha256, profiles[i % 5], "en", "gps", Double.parseDouble(coordinates[1]), Double.parseDouble(coordinates[0]), 1, "1.8.0.", "", "true", "true", "false", "appID", "uid2", timestamp);
                        // increment date by 10 minutes
                        date = new Date(date.getTime() + 600000);
                    }
                    counter++;

                }
            } catch (IOException ex) {
                Logger.getLogger(SortBasedOnDate.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
    }

    // estimate the maximum FirenzeWifi RSSI
    public static void estimateRSSI() {
        FileWriter fstream = null;
        try {
            init();
            long limit = 10000;
            long offset = 0;
            double average_a = 0;
            double max_a = 0;
            double min_a = 100000000;
            long size = 0;
            int i = 0;
            fstream = new FileWriter("C:\\Users\\cenni\\Downloads\\out.txt");
            BufferedWriter out = new BufferedWriter(fstream);
            try {
                do {
                    String json_url = "http://www.disit.org/sensor/api_select.php?action=get_sensors&type=json&limit=" + limit + "&offset=" + offset;
                    URL url = new URL(json_url);
                    BufferedReader in = new BufferedReader(new InputStreamReader(url.openStream(), "UTF-8"));
                    JSONParser p = new JSONParser();
                    Object o = p.parse(in);
                    JSONArray json_array = (JSONArray) o;
                    json_array = getSortedList(json_array);
                    size = json_array.size();
                    offset += limit;
                    for (Object json : json_array) {
                        JSONObject obj = (JSONObject) json;
                        // if this is a FirenzeWifi access point
                        if (((String) obj.get("network_name")).equalsIgnoreCase("FirenzeWiFi") && !((String) obj.get("rssi (dB)")).equals("")) {
                            String sparql = "PREFIX schema:<http://schema.org/>\n"
                                    + "PREFIX dcterms:<http://purl.org/dc/terms/>\n"
                                    + "SELECT * WHERE{\n"
                                    + "?s a km4c:Wifi.\n"
                                    + "?s schema:name ?n.\n"
                                    + "OPTIONAL{?s dcterms:description ?d.}\n"
                                    + "OPTIONAL{?s schema:streetAddress ?a.}\n"
                                    + "OPTIONAL{?s km4c:houseNumber ?nc.}\n"
                                    + "{?s skos:note ?nt.}\n"
                                    + "?s geo:lat ?lat.\n"
                                    + "?s geo:long ?long.\n"
                                    + "?s geo:geometry ?geo.\n"
                                    + "FILTER(bif:st_distance(?geo, bif:st_point(" + (String) obj.get("longitude") + "," + (String) obj.get("latitude") + ")) <= 0.04)\n"
                                    + "BIND(bif:st_distance(?geo, bif:st_point(" + (String) obj.get("longitude") + "," + (String) obj.get("latitude") + ")) AS ?dist)\n"
                                    + "} ORDER BY ?dist LIMIT 1";
                            String rdf_url = settings.get("rdf_url") + "?query=" + URLEncoder.encode(sparql, "UTF-8") + "&format=json";
                            JSONObject results = getJSON(rdf_url);
                            // if results are empty then return
                            if (results.get("results") == null) {
                                continue;
                            }
                            results = (JSONObject) results.get("results");
                            JSONArray list = (JSONArray) results.get("bindings");
                            //System.out.println("idmeasure: " + (String) obj.get("idmeasure") + " rssi: " + (String) obj.get("rssi (dB)"));
                            for (Object rdf_obj : list) {
                                JSONObject rdf_o = (JSONObject) rdf_obj;
                                rdf_o = (JSONObject) rdf_o.get("dist");
                                String dist = (String) rdf_o.get("value"); // serviceUri
                                rdf_o = (JSONObject) rdf_obj;
                                rdf_o = rdf_o.get("d") != null ? (JSONObject) rdf_o.get("d") : null;
                                String d = rdf_o != null ? (String) rdf_o.get("value") : ""; // subclass
                                double A = Double.parseDouble((String) obj.get("rssi (dB)")) + 30 * Math.log10(Double.parseDouble(dist) * 1000);
                                rdf_o = (JSONObject) rdf_obj;
                                rdf_o = (JSONObject) rdf_o.get("nt");
                                String nt = rdf_o != null ? (String) rdf_o.get("value") : "";
                                rdf_o = (JSONObject) rdf_obj;
                                rdf_o = (JSONObject) rdf_o.get("lat");
                                String lat = rdf_o != null ? (String) rdf_o.get("value") : "";
                                rdf_o = (JSONObject) rdf_obj;
                                rdf_o = (JSONObject) rdf_o.get("long");
                                String lng = rdf_o != null ? (String) rdf_o.get("value") : "";
                                //System.out.println("idmeasure: " + (String) obj.get("idmeasure") + " rssi: " + (String) obj.get("rssi (dB)") + " dist: " + dist + " d: " + d + " A: " + A);
                                out.write((String) obj.get("idmeasure") + ";" + (String) obj.get("MAC_address") + ";" + nt + ";"
                                        + (String) obj.get("rssi (dB)") + ";" + dist + ";" + d + ";" + (String) obj.get("latitude") + ";"
                                        + (String) obj.get("longitude") + ";" + lat + ";" + lng + "\n"
                                );
                                average_a += A;
                                if (A > max_a) {
                                    max_a = A;
                                }
                                if (A < min_a) {
                                    min_a = A;
                                }
                                i++;
                            }
                        }
                    }
                } while (size > 0);
                out.close();
                System.out.println("average A: " + (average_a / i) + " i: " + i + " A min: " + min_a + " A max: " + max_a);
                System.out.println("done");

            } catch (MalformedURLException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            } catch (UnsupportedEncodingException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            } catch (IOException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            } catch (ParseException | JSONException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }

        } catch (IOException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                fstream.close();

            } catch (IOException ex) {
                Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
    }

    public static ArrayList<String> getMacroclasses() {
        Connection conn = null;
        ArrayList<String> result = new ArrayList<>();
        try {
            conn = getConnection();
            PreparedStatement preparedStatement;

            preparedStatement = conn.prepareStatement("SELECT * FROM recommender.categories WHERE macroclass=\"\"");
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                result.add(rs.getString("key"));

            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
        return result;
    }

    // do not use, used for syncing old stats
    public static void populateUsers() {
        Connection conn = null;
        try {
            conn = Recommender.getConnection();
            // get recommendations
            PreparedStatement preparedStatement = conn.prepareStatement("SELECT * FROM recommender.recommendations_log");
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                ArrayList<String> tmp = new ArrayList<>();
                long id = rs.getLong("id");
                String json = rs.getString("recommendations");
                tmp = RecommenderLoggerStatus.getServiceURIsFromJSON(tmp, json);
                PreparedStatement updateStatement = conn.prepareStatement("UPDATE recommender.recommendations_log SET nrecommendations = ? WHERE id = ?");
                updateStatement.setInt(1, tmp.size());
                updateStatement.setLong(2, id);
                updateStatement.executeUpdate();
            }
            // close connection
            conn.close();

        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }

            }
        }
    }
}

// class to sort a json array by date
class SortBasedOnDate implements Comparator<JSONObject> {
    /*
     * (non-Javadoc)
     *
     * @see java.util.Comparator#compare(java.lang.Object, java.lang.Object)
     * lhs- 1st message in the form of json object. rhs- 2nd message in the form
     * of json object.
     */

    @Override
    public int compare(JSONObject lhs, JSONObject rhs) {
        try {
            SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
            Date date1 = sdf.parse((String) lhs.get("date"));
            Date date2 = sdf.parse((String) rhs.get("date"));
            return date1.compareTo(date2) > 0 ? 1 : (date1.compareTo(date2) < 0 ? -1 : 0);
        } catch (java.text.ParseException ex) {
            Logger.getLogger(SortBasedOnDate.class.getName()).log(Level.SEVERE, null, ex);
        }
        return 0;
    }
}
