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

/**
 *
 * @author Daniele Cenni, daniele.cenni@unifi.it
 */
// class to log the recommender status (used in Recommender.java)
import java.io.IOException;
import java.nio.file.FileSystems;
import java.nio.file.FileStore;
import java.net.Inet4Address;
import java.net.InetAddress;
import java.net.NetworkInterface;
import java.net.SocketException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Enumeration;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Properties;
import java.util.logging.Level;
import java.util.logging.Logger;
import org.json.simple.JSONArray;
import org.json.simple.JSONObject;
import org.json.simple.parser.JSONParser;
import org.json.simple.parser.ParseException;

public class RecommenderLoggerStatus implements Runnable {

    private static String ip = null;
    private static HashMap<String, String> serviceUri_serviceType;

    public RecommenderLoggerStatus() {
    }

    @Override
    public void run() {
        Recommender.loadSettings();
        HashMap<String, String> settings = Recommender.getSettings();
        if (!settings.get("log_stats").equals("true")) {
            return;
        }
        int days = Integer.parseInt(settings.get("log_recommendations_statistics_days"));
        serviceUri_serviceType = new HashMap<>();
        //calculateGeneralRecommendationsStats();
        calculateUsersRecommendationStats(days);
    }

    //get the ipv4 addresses, semicolon separated, of the current network interface (excluding 127.0.0.1)
    public static String getIpAddress() {
        if (ip == null) {
            try {
                for (Enumeration en = NetworkInterface.getNetworkInterfaces(); en.hasMoreElements();) {
                    NetworkInterface intf = (NetworkInterface) en.nextElement();
                    for (Enumeration enumIpAddr = intf.getInetAddresses(); enumIpAddr.hasMoreElements();) {
                        InetAddress inetAddress = (InetAddress) enumIpAddr.nextElement();
                        if (!inetAddress.isLoopbackAddress() && inetAddress instanceof Inet4Address) {
                            String ipAddress = inetAddress.getHostAddress();
                            ip = (ip == null ? "" : ip + ";") + ipAddress;
                        }
                    }
                }
            } catch (SocketException ex) {
                Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
                return "";
            }
        }
        return ip;
    }

    //Returns the size, in bytes, of the file store
    public long getTotalSpace() {
        long result = 0;
        for (FileStore store : FileSystems.getDefault().getFileStores()) {
            try {
                result += store.getTotalSpace();
            } catch (IOException ex) {
                Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
            }
        }
        return result;
    }

    //Returns the number of unallocated bytes in the file store
    public long getUnallocatedSpace() {
        long result = 0;
        for (FileStore store : FileSystems.getDefault().getFileStores()) {
            try {
                result += store.getUnallocatedSpace();
            } catch (IOException ex) {
                Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
            }
        }
        return result;
    }

    //Returns the number of bytes available to this Java virtual machine on the file store
    public long getUsableSpace() {
        long result = 0;
        for (FileStore store : FileSystems.getDefault().getFileStores()) {
            try {
                result += store.getUsableSpace();
            } catch (IOException ex) {
                Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
            }
        }
        return result;
    }

    // get of active users in the last n days
    public static long activeUsersLastNDays(long days) {
        Connection conn = null;
        long users = 0;
        try {
            conn = Recommender.getConnection();
            PreparedStatement preparedStatement;
            preparedStatement = conn.prepareStatement("SELECT COUNT(DISTINCT(user)) AS num FROM recommender.recommendations_log WHERE timestamp > date(NOW() - INTERVAL ? DAY)");
            preparedStatement.setLong(1, days);
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                users = rs.getLong("num");
            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
            return users;
        } finally {
            if (conn != null) {
                try {
                    conn.close();
                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
        return users;
    }

    // get the number of recommendations sent to the users in the last n days
    public static long recommendationsLastNDays(long days) {
        Connection conn = null;
        long users = 0;
        try {
            conn = Recommender.getConnection();
            PreparedStatement preparedStatement;
            preparedStatement = conn.prepareStatement("SELECT COUNT(*) AS num FROM recommender.recommendations_log WHERE timestamp > date(NOW() - INTERVAL ? DAY)");
            preparedStatement.setLong(1, days);
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                users = rs.getLong("num");
            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
            return users;
        } finally {
            if (conn != null) {
                try {
                    conn.close();
                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
        return users;
    }

    // get the number of recommendations and views after them in the last n days
    public static long[] recommendations_views(long days) {
        Connection conn = null;
        long[] result = new long[2];
        try {
            conn = Recommender.getConnection();
            PreparedStatement preparedStatement;
            preparedStatement = conn.prepareStatement("SELECT SUM(nrecommendations) AS nrecommendations, SUM(nviews) AS nviews FROM recommender.users_stats WHERE timestamp > NOW() - INTERVAL ? DAY");
            preparedStatement.setLong(1, days);
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                result[0] = rs.getLong("nviews");
                result[1] = rs.getLong("nrecommendations");
            }
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
            return result;
        } finally {
            if (conn != null) {
                try {
                    conn.close();
                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
        return result;
    }

    //get human readable bytes, not used
    public static String humanReadableByteCount(long bytes, boolean si) {
        int unit = si ? 1000 : 1024;
        if (bytes < unit) {
            return bytes + " B";
        }
        int exp = (int) (Math.log(bytes) / Math.log(unit));
        String pre = (si ? "kMGTPE" : "KMGTPE").charAt(exp - 1) + (si ? "" : "i");
        return String.format("%.1f %sB", bytes / Math.pow(unit, exp), pre);
    }

    // calculate and insert into database the recommendations stats for the last n days
    public static void calculateUsersRecommendationStats(long days) {
        Connection recommender_conn = null;
        Connection access_log_conn = null;
        Properties prop = Recommender.getProperties();
        HashMap<String, HashMap<String, ArrayList<String>>> recommendations = new HashMap<>(); // user => (timestamp =>  recommendations list)
        HashMap<String, HashMap<String, ArrayList<String>>> views = new HashMap<>(); // user => (serviceUri =>  arraylist of timestamps at which this serviceUri was viewed)
        try {
            recommender_conn = Recommender.getConnection();
            access_log_conn = Recommender.getConnection(prop.getProperty("db_access_log_hostname"),
                    prop.getProperty("db_access_log_schema"),
                    prop.getProperty("db_access_log_username"),
                    prop.getProperty("db_access_log_password"));
            // get recommendations
            PreparedStatement preparedStatement = recommender_conn.prepareStatement("SELECT * FROM recommender.recommendations_log " + (days > 0 ? "WHERE timestamp > NOW() - INTERVAL ? DAY" : ""));
            if (days > 0) {
                preparedStatement.setLong(1, days);
            }
            ResultSet rs = preparedStatement.executeQuery();
            while (rs.next()) {
                String user = rs.getString("user");
                String json = rs.getString("recommendations");
                if (recommendations.get(user) != null) {
                    HashMap<String, ArrayList<String>> timestamp_serviceURIs = recommendations.get(user); // timestamp => recommendations list
                    if (timestamp_serviceURIs.get(rs.getString("timestamp")) != null) {
                        ArrayList<String> tmp = getServiceURIsFromJSON((ArrayList<String>) timestamp_serviceURIs.get(rs.getString("timestamp")), json);
                        recommendations.get(user).put(rs.getString("timestamp"), tmp);
                    } else {
                        ArrayList<String> tmp = new ArrayList<>();
                        tmp = getServiceURIsFromJSON(tmp, json);
                        recommendations.get(user).put(rs.getString("timestamp"), tmp);
                    }
                } else {
                    HashMap<String, ArrayList<String>> tmp = new HashMap<>();
                    ArrayList<String> t = new ArrayList<>();
                    t = getServiceURIsFromJSON(t, json);
                    tmp.put(rs.getString("timestamp"), t);
                    recommendations.put(user, tmp);
                }
            }

            // check the Access Log to see if recommendations were viewed by the users
            Iterator it1 = recommendations.keySet().iterator();
            // for each user
            while (it1.hasNext()) {
                String user = (String) it1.next();
                HashMap<String, ArrayList<String>> serviceURIsMap = recommendations.get(user);
                Iterator it2 = serviceURIsMap.keySet().iterator();
                // for each recommendation timestamp
                while (it2.hasNext()) {
                    String timestamp = (String) it2.next();
                    ArrayList<String> serviceURIsList = (ArrayList<String>) serviceURIsMap.get(timestamp);
                    String serviceURIs = "(";
                    // for each serviceUri
                    for (int i = 0; i < serviceURIsList.size(); i++) {
                        serviceURIs += (i != 0 ? " OR " : "") + "serviceURI = '" + serviceURIsList.get(i) + "'";
                    }
                    serviceURIs += ")";
                    // if there are views for this recommendation timestamp
                    if (serviceURIsList.size() > 0) {
                        preparedStatement = access_log_conn.prepareStatement("SELECT serviceUri, timestamp FROM ServiceMap.AccessLog WHERE categories IS NOT NULL AND uid = ? AND " + serviceURIs + " AND mode = ? AND timestamp >= ?");
                        preparedStatement.setString(1, user);
                        preparedStatement.setString(2, "api-service-info");
                        preparedStatement.setString(3, timestamp);
                        rs = preparedStatement.executeQuery();

                        // populate views hashmap with data
                        while (rs.next()) {
                            String serviceType = serviceUri_serviceType.get(rs.getString("serviceUri"));
                            String macroclass = "";
                            String subclass = "";
                            if (serviceType != null) {
                                macroclass = serviceType.substring(0, serviceType.indexOf("_"));
                                subclass = serviceType.substring(serviceType.indexOf("_") + 1);
                            }
                            PreparedStatement updateStatement = recommender_conn.prepareStatement("INSERT IGNORE INTO recommender.recommendations_stats (user, serviceUri, macroclass, subclass, recommendedAt, viewedAt) VALUES (?, ?, ?, ?, ?, ?)");
                            updateStatement.setString(1, user);
                            updateStatement.setString(2, rs.getString("serviceUri"));
                            updateStatement.setString(3, macroclass);
                            updateStatement.setString(4, subclass);
                            updateStatement.setString(5, timestamp);
                            updateStatement.setString(6, rs.getString("timestamp"));
                            updateStatement.executeUpdate();
                        }
                    }
                }
            }
            // close connection to recommender database
            recommender_conn.close();
            // close connection to AccessLog database
            access_log_conn.close();
        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (recommender_conn != null) {
                try {
                    recommender_conn.close();
                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
                }
            }
            if (access_log_conn != null) {
                try {
                    access_log_conn.close();
                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
    }

    // calculate and insert into database general recommendations stats, do not use
    public static void calculateGeneralRecommendationsStats() {
        Connection conn = null;
        long nrecommendations_1_day = recommendationsLastNDays(1); // the number of recommendations in the last day
        long nrecommendations_7_days = recommendationsLastNDays(1); // the number of recommendations in the last 7 days
        double nrecommendations_per_hour = Math.round(100d * nrecommendations_1_day / 24) / 100d; // the number of recommendations per hour in the last day
        long active_users_1_day = activeUsersLastNDays(1); // the number of active users in the last day
        long active_users_7_days = activeUsersLastNDays(7); // the number of active users in the last 7 days
        double recommendations_per_user_1_day = Math.round(100d * nrecommendations_1_day / active_users_1_day) / 100d; // the number of recommendations per user in the last day
        double recommendations_per_user_7_days = Math.round(100d * nrecommendations_7_days / active_users_7_days) / 100d; // the number of recommendations per user in the last 7 days
        long[] recommendations_views_1_day = recommendations_views(1); // the number of recommendations and views after them in the last day
        long[] recommendations_views_7_days = recommendations_views(7); // the number of recommendations and views after them in the last 7 days
        double views_over_recs_1_day = (recommendations_views_1_day[0] > 0 && recommendations_views_1_day[1] > 0 ? recommendations_views_1_day[0] / recommendations_views_1_day[1] : 0);
        double views_over_recs_7_days = (recommendations_views_7_days[0] > 0 && recommendations_views_7_days[1] > 0 ? recommendations_views_7_days[0] / recommendations_views_7_days[1] : 0);

        try {
            conn = Recommender.getConnection();
            // get recommendations
            PreparedStatement preparedStatement = conn.prepareStatement("INSERT INTO recommender.general_stats (recs_24_h, recs_7_days, recs_per_hour, active_users_24_h, active_users_7_days, recs_per_user_24_h, recs_per_user_7_days, views_after_recs_over_recs_24_h, views_after_recs_over_recs_7_days) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)");
            preparedStatement.setLong(1, nrecommendations_1_day);
            preparedStatement.setLong(2, nrecommendations_7_days);
            preparedStatement.setDouble(3, nrecommendations_per_hour);
            preparedStatement.setLong(4, active_users_1_day);
            preparedStatement.setLong(5, active_users_7_days);
            preparedStatement.setDouble(6, recommendations_per_user_1_day);
            preparedStatement.setDouble(7, recommendations_per_user_7_days);
            preparedStatement.setDouble(8, views_over_recs_1_day);
            preparedStatement.setDouble(9, views_over_recs_7_days);
            preparedStatement.executeUpdate();

        } catch (SQLException ex) {
            Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
        } finally {
            if (conn != null) {
                try {
                    conn.close();
                } catch (SQLException ex) {
                    Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
                }
            }
        }
    }

    // get the serviceURIs from a recommendations JSON
    public static ArrayList<String> getServiceURIsFromJSON(ArrayList<String> recommendedServiceURIs, String json) {
        try {
            JSONParser parser = new JSONParser();
            JSONObject obj1 = (JSONObject) parser.parse(json);
            // iterate groups
            Iterator groups = obj1.keySet().iterator();
            while (groups.hasNext()) {
                String group = (String) groups.next();
                if (obj1.get(group) instanceof JSONArray) { // this exclude the Weather group that is a JSONObject
                    JSONArray obj2 = (JSONArray) obj1.get(group);
                    Iterator it = obj2.iterator();
                    while (it.hasNext()) {
                        JSONObject obj3 = (JSONObject) it.next();
                        Iterator srv = obj3.keySet().iterator();
                        while (srv.hasNext()) {
                            String key = (String) srv.next(); // this could be "Service" or "BusStop" or "Event"
                            if (obj3.get(key) instanceof JSONObject) {
                                JSONObject obj4 = (JSONObject) obj3.get(key);
                                if (obj4.get("features") instanceof JSONArray) {
                                    JSONArray obj5 = (JSONArray) obj4.get("features");
                                    if (((JSONObject) obj5.get(0)).get("properties") instanceof JSONObject) {
                                        JSONObject obj6 = (JSONObject) ((JSONObject) obj5.get(0)).get("properties");
                                        if (obj6.get("serviceUri") != null) {
                                            // if the array lsit of serviceURIs for this user does not contain this serviceURI
                                            //if (!Arrays.asList(recommendedServiceURIs).contains((String) obj6.get("serviceUri"))) {
                                            recommendedServiceURIs.add((String) obj6.get("serviceUri"));
                                            // map serviceUri to serviceType
                                            String serviceUri = (String) obj6.get("serviceUri");
                                            String serviceType = !serviceUri.contains("Event") ? (String) obj6.get("serviceType") : "Events_Event";
                                            serviceUri_serviceType.put(serviceUri, serviceType);
                                            //System.out.println("serviceUri: " + ((String) obj6.get("serviceUri")));
                                            //}
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (ParseException ex) {
            Logger.getLogger(Recommender.class.getName()).log(Level.SEVERE, null, ex);
        }
        return recommendedServiceURIs;
    }

    public static JSONArray ArrayListToJSON(ArrayList<String> list) {
        JSONArray array = new JSONArray();
        for (String s : list) {
            array.add(s);
        }
        return array;
    }
}
