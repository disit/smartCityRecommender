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
import java.io.BufferedReader;
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
import java.util.Date;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Properties;
import java.util.concurrent.ScheduledExecutorService;
import java.util.logging.Level;
import javax.sql.DataSource;
import org.apache.commons.collections.BidiMap;
import org.apache.commons.collections.bidimap.TreeBidiMap;
import org.apache.mahout.cf.taste.common.Refreshable;
import org.apache.mahout.cf.taste.common.TasteException;
import org.apache.mahout.cf.taste.impl.common.FastIDSet;
import org.apache.mahout.cf.taste.impl.common.RefreshHelper;
import org.apache.mahout.cf.taste.impl.model.jdbc.MySQLJDBCDataModel;
import org.apache.mahout.cf.taste.impl.model.jdbc.ReloadFromJDBCDataModel;
import org.apache.mahout.cf.taste.impl.recommender.AbstractRecommender;
import org.apache.mahout.cf.taste.impl.recommender.AllUnknownItemsCandidateItemsStrategy;
import org.apache.mahout.cf.taste.impl.recommender.GenericRecommendedItem;
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
import org.apache.wink.json4j.OrderedJSONObject;
import org.json.simple.JSONArray;
import org.json.simple.JSONObject;
import org.json.simple.parser.JSONParser;
import org.json.simple.parser.ParseException;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import static recommender.Recommender.getAlreadyRecommendedTweets;
import static recommender.Recommender.getConnection;
import static recommender.Recommender.getDislikedGroups;
import static recommender.Recommender.getDislikedSubclasses;
import static recommender.Recommender.getJSON;
import static recommender.Recommender.getPriorityForGroup;

/**
 * @author Daniele Cenni, daniele.cenni@unifi.it
 * {@link org.apache.mahout.cf.taste.recommender.Recommender} that uses matrix
 * factorization (a projection of users and items onto a feature space)
 */
public final class MyRecommender extends AbstractRecommender {

    private Factorization factorization;
    private Factorizer factorizer;
    private final PersistenceStrategy persistenceStrategy;
    private final RefreshHelper refreshHelper;

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

    // logger
    private static ScheduledExecutorService scheduledThreadPool;

    private static final Logger log = LoggerFactory.getLogger(MyRecommender.class);

    public MyRecommender(DataModel dataModel, Factorizer factorizer) throws TasteException {
        this(dataModel, factorizer, new AllUnknownItemsCandidateItemsStrategy(), getDefaultPersistenceStrategy());
    }

    public MyRecommender(DataModel dataModel, Factorizer factorizer, CandidateItemsStrategy candidateItemsStrategy)
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
    public MyRecommender(DataModel dataModel, Factorizer factorizer, PersistenceStrategy persistenceStrategy)
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
    public MyRecommender(DataModel dataModel, Factorizer factorizer, CandidateItemsStrategy candidateItemsStrategy,
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

    //@Override
    public List<RecommendedItem> recommend1(long userID, int howMany, IDRescorer rescorer, boolean includeKnownItems)
            throws TasteException {
        Preconditions.checkArgument(howMany >= 1, "howMany must be at least 1");
        log.debug("Recommending items for user ID '{}'", userID);
        System.out.println("Recommending items for user ID: " + userID);

        PreferenceArray preferencesFromUser = getDataModel().getPreferencesFromUser(userID);
        FastIDSet possibleItemIDs = getAllOtherItems(userID, preferencesFromUser, includeKnownItems);

        List<RecommendedItem> topItems = TopItems.getTopItems(howMany, possibleItemIDs.iterator(), rescorer,
                new Estimator(userID));
        log.debug("Recommendations are: {}", topItems);

        System.out.println("Recommendations are: " + topItems);

        return topItems;
    }

    // recommend, timestamp must be null in production (used by loadRecommender)
    // if log is true, then log the recommendation to the recommender.recommendations_log MySQL table
    @Override
    public List<RecommendedItem> recommend(long userID, int howMany, IDRescorer rescorer, boolean includeKnownItems)
            throws TasteException {
        // init recommender and load settings (only if recommender is null)
        init();

        // load settings
        loadSettings();

        // load groups with their priorities
        loadGroups();

        // load groups with their translations
        loadGroupsLangs();

        // set user preferences
        /*if (aroundme == null) {
         setUserPreferences(user, profile, userAgent);
         }
         // refresh the recommender (delete cache)
         if (aroundme == null) {
         svdRecommender.refresh(null);
         }*/
        svdRecommender.refresh(null);

        // get the list of recommendations grouped per group (key = group, value = ArrayList of recommendations)
        // if aroundme = true then svdEnabled = false
        String user = getUser(userID);
        if (user == null || userID == 0) {
            return new ArrayList<>();
        }
        String profile = getUserProfile(user);
        //System.out.println("user: " + user + " profile: " + profile);
        String[] location = getPreferredUserLocation(user);
        System.out.println("Location: " + location[0] + ", " + location[1]);
        Object[] obj = getRecommendations(user, profile, "true", Double.parseDouble(location[0]), Double.parseDouble(location[1]), 2); // obj[0] = list of recommendations, obj[1] = sparql query, obj[2] = disliked subclasses, obj[3] = disliked groups
        HashMap<String, ArrayList<HashMap<String, String>>> recommendations = obj[0] != null ? (HashMap<String, ArrayList<HashMap<String, String>>>) obj[0] : null;
        // disliked subclasses
        ArrayList<String> dislikedSubclasses = obj[1] != null ? (ArrayList<String>) obj[1] : null;
        // disliked groups
        ArrayList<String> dislikedGroups = obj[2] != null ? (ArrayList<String>) obj[2] : null;
        String sparql = (String) obj[3];

        // update user profile
        //updateUserProfile(user, profile);
        // get the json result
        // aroundme = true has precedence on alreadyRecommended
        List<RecommendedItem> topItems = getRecommendationsJSON(recommendations, user, profile, "en", "gps", 43.798673, 11.2535434, "sparql", 2, "", dislikedSubclasses, dislikedGroups, "1.1.0", null, "true", "appID", "uid2");
        return topItems;
    }

    public void init() {
        if (svdRecommender == null) {
            try {
                // load properties file, includes database settings (url, username, password) and connection pool maximum number of connections (used by ConnectionPool)
                prop = new Properties();
                prop.load(Recommender.class.getResourceAsStream("settings.properties"));

                // load settings from MySQL database
                loadSettings();

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

                /*dm = new MySQLJDBCDataModel(
                 mysql_datasource, "preferences", "user_id",
                 "item_id", "preference", "timestamp");*/
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
                java.util.logging.Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
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
            ArrayList<String> subclasses = users_profiles.get(profile);
            for (String subclass : subclasses) {
                if (!dislikedSubclasses.contains(subclass)) {
                    String t = subclass.equalsIgnoreCase("Bus") ? "BusStop" : subclass;
                    t = subclass.equalsIgnoreCase("Events") ? "Event" : subclass;
                    recommendations_list.add(t);
                }
            }
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
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
        return new Object[]{result, dislikedSubclasses, dislikedGroups, sparql};
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
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                java.util.logging.Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
    }

    private static JSONObject postRequest(String url, String parameters) {
        Object o = null;
        try {
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
            o = p.parse(in);
            in.close();
        } catch (MalformedURLException ex) {
            java.util.logging.Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
            return null;
        } catch (IOException ex) {
            java.util.logging.Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
            return null;
        } catch (ParseException ex) {
            java.util.logging.Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
        }
        return (JSONObject) o;
    }

    // get data for recommendations from ServiceMap
    // this method return a list of recommendations, grouped per groups (reported in the table 'groups' of the MySQL database), as defined for each user profile (e.g. student)
    // the key of the serviceURIsList is the user profile, and the value is an array list of recommendations
    public static List<RecommendedItem> getRecommendationsJSON(HashMap<String, ArrayList<HashMap<String, String>>> serviceURIsList, String user, String profile, String language, String mode, double latitude, double longitude, String sparql, double distance, String timestamp, ArrayList<String> dislikedSubclassesList, ArrayList<String> dislikedGroupsList, String version, HashMap<String, Long> timing, String aroundme, String appID, String uid2) {
        // Extension of the basic JSONObject. This class allows control of the serialization order of attributes
        // The order in which items are put into the instance controls the order in which they are serialized out. For example, the last item put is the last item serialized.
        OrderedJSONObject obj_log = new OrderedJSONObject();
        JSONArray array = new JSONArray();
        Connection conn = null;
        List<RecommendedItem> topItems = new ArrayList<>();
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
                    /*if (!dislikedGroupsList.contains("Weather")) {
                     // insert weather, this is not part of groups and it is always provided
                     JSONObject weather = getWeather(latitude, longitude);
                     if (weather != null) {
                     obj.put("suggestions", weather);
                     obj_log.put("Weather", weather); // json to be logged to MySQL table
                     empty = false; // means that this suggestions have to be added to the JSON returned by this function
                     }
                     }
                     if (!dislikedGroupsList.contains("Twitter1") && version != null) {
                     JSONObject tweets = getTweets(user, profile, "Twitter1", assessor);
                     array.add(tweets);
                     }
                     if (!dislikedGroupsList.contains("Twitter2") && version != null) {
                     JSONObject tweets = getTweets(user, profile, "Twitter2", assessor);
                     array.add(tweets);
                     }
                     if (!dislikedGroupsList.contains("Twitter3") && version != null) {
                     JSONObject tweets = getTweets(user, profile, "Twitter3", assessor);
                     array.add(tweets);
                     }
                     // if the weather suggestion has to be added to the JSON returned by this function
                     if (!empty) {
                     array.add(obj);
                     }*/
                    break;
                }

                /*obj.put("group", group); // original group name (english)
                 HashMap<String, String> langMap = (HashMap<String, String>) groups_langs.get(group);
                 if (language.equalsIgnoreCase("en")) {
                 obj.put("label", group);
                 } else {
                 obj.put("label", langMap != null ? langMap.get(language) : group); // translated group name in the language specified by lang
                 }
                 obj.put("priority", i);
                 if (assessor == 1) {
                 obj.put("assessor", 1);
                 }*/
                //JSONArray json_array = new JSONArray();
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
                            /*String url = settings.get("service_map_url") + "?serviceUri=" + serviceURIMap.get("serviceURI") + "&format=json" + (group.equals("Bus") ? "&realtime=false" : "");
                             JSONObject json = getJSON(url);
                             if (json == null) {
                             continue;
                             }
                             json_array.add(json);*/
                            String serviceURI = serviceURIMap.get("serviceURI");
                            Object[] itemID_preference = getItemIDPreference(serviceURI);
                            long item_id = (long) itemID_preference[0];
                            double preference = (double) itemID_preference[1];
                            //System.out.println(item_id + " " + preference);
                            topItems.add(new GenericRecommendedItem(item_id, (float) preference));
                            counter++;
                        }
                    }
                    /*if (json_array.size() > 0) {
                     obj.put("suggestions", json_array);
                     obj_log.put(group, json_array); // json to be logged to MySQL table
                     empty = false; // means that this suggestions have to be added to the JSON returned by this function
                     }*/
                } // if this group is Weather and the user did not disliked it (within the time period defined in the setting ignore_dislike_groups_days)
                /*else if (group.equals("Weather") && !dislikedGroupsList.contains("Weather")) {
                 // insert weather, this is not part of groups and it is always provided
                 JSONObject weather = getWeather(latitude, longitude);
                 if (weather != null) {
                 obj.put("suggestions", weather);
                 obj_log.put("Weather", weather); // json to be logged to MySQL table
                 empty = false; // means that this suggestions have to be added to the JSON returned by this function
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
                 // if some suggestions have to be added to the JSON returned by this function
                 if (!empty) {
                 array.add(obj);
                 }*/
            }
        } catch (NumberFormatException ex) {
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    java.util.logging.Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
        return topItems;
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
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                java.util.logging.Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return profile;
    }

    // get the user id from MySQL database (insert the user if not present)
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
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                java.util.logging.Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return id;
    }

    // get the user from MySQL database (insert the user if not present)
    public static String getUser(long userId) {
        Connection conn = null;
        String user = "";
        try {
            conn = getConnection();

            PreparedStatement preparedStatement = conn.prepareStatement("SELECT user FROM recommender.users WHERE id = ?");
            preparedStatement.setLong(1, userId);
            ResultSet rs = preparedStatement.executeQuery();

            while (rs.next()) {
                user = rs.getString("user");
            }
        } catch (SQLException ex) {
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                java.util.logging.Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return user;
    }

    // get the item id from MySQL database (insert the item if not present)
    public static Object[] getItemIDPreference(String item) {
        Connection conn = null;
        long id = 0;
        double preference = 0;
        try {
            conn = getConnection();

            PreparedStatement preparedStatement = conn.prepareStatement("SELECT item_id, preference FROM recommender.assessment_new WHERE item_id IN (SELECT id FROM recommender.assessment_items WHERE item = ?)");
            preparedStatement.setString(1, item);
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                id = rs.getLong("item_id");
                preference = rs.getDouble("preference");
            }
        } catch (SQLException ex) {
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                java.util.logging.Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return new Object[]{id, preference};
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
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    java.util.logging.Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
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
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();

                }
            } catch (SQLException ex) {
                java.util.logging.Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
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
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
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
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    java.util.logging.Logger.getLogger(Recommender.class
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
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();

                } catch (SQLException ex) {
                    java.util.logging.Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
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
            long userID = getUserID(user);
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
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
        return new Object[]{recommendations_list, dislikedSubclasses, dislikedGroups};
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
                + "SELECT DISTINCT ?s ?t ?name ?address ?civic ?dist WHERE {\n"
                + "?s a ?t.\n"
                + "FILTER(?t!=km4c:RegularService && ?t!=km4c:Service && \n"
                + "?t!=km4c:DigitalLocation && ?t!=km4c:TransverseService && ?t!=gtfs:Stop)\n"
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
                + "     OPTIONAL {?s km4c:houseNumber ?civic }} ORDER BY ?dist";
        //System.out.println(result);
        return result;
    }

    // get the group of a subclass
    public static String getSubclassGroup(String subclass) {
        HashMap<String, String> t = categories.get(subclass.substring(subclass.lastIndexOf("#") + 1));
        if (t != null) {
            return t.get("group");
        }
        return null;
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
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);

            return null;
        } catch (SQLException ex) {
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);

            return null;
        } catch (Exception ex) {
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);

            return null;
        }
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
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                if (conn != null) {
                    conn.close();
                }
            } catch (SQLException ex) {
                java.util.logging.Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }
        }
        return new long[]{assessor, id};
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
            java.util.logging.Logger.getLogger(Recommender.class
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
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();
                } catch (SQLException ex) {
                    java.util.logging.Logger.getLogger(Recommender.class
                            .getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
        return obj;
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
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
        return null;
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
                java.util.logging.Logger.getLogger(Recommender.class
                        .getName()).log(Level.SEVERE, null, ex);
            }

        } catch (MalformedURLException ex) {
            java.util.logging.Logger.getLogger(Recommender.class
                    .getName()).log(Level.SEVERE, null, ex);
        }
        return result;
    }

    // get the most preferred user location from AccessLog
    public static String[] getPreferredUserLocation(String userID) {
        Connection conn = null;
        String[] loc = {"43.798673", "11.2535434"};
        String[] location = loc;
        try {
            conn = getConnection();
            PreparedStatement preparedStatement1 = conn.prepareStatement("SELECT selection, COUNT(*) AS num FROM recommender.AccessLog WHERE uid = \"" + userID + "\" AND selection IS NOT NULL GROUP BY selection ORDER BY num DESC LIMIT 1");
            ResultSet rs = preparedStatement1.executeQuery();
            while (rs.next()) {
                location = rs.getString("selection").split(";");
                location = location.length == 2 ? location : loc;
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
        return location;
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
