<%-- 
    Document   : index
    Created on : 2-set-2015, 9.19.15
    Author     : Daniele Cenni
    email      : daniele.cenni@unifi.it
--%>

<%@page import="smartcityrecommender.*" %>
<%@page import="java.util.TreeMap" %>
<%@page import="java.util.ArrayList" %>
<%@page import="java.util.HashMap" %>
<%@page contentType="text/html" pageEncoding="UTF-8"%>
<%
response.addHeader("Access-Control-Allow-Origin", "*");
// get action (recommend, insert_preference, delete_preference)
if(request.getParameter("action") != null && request.getParameter("action").equalsIgnoreCase("recommend") &&
   (request.getParameter("user") != null || request.getParameter("uid") != null) && 
   request.getParameter("profile") != null && 
   (request.getParameter("language") != null || request.getParameter("lang") != null) && 
   request.getParameter("latitude") != null &&
   request.getParameter("longitude") != null &&
   request.getParameter("distance") != null) {
   out.write(Recommender.recommend((String)request.getParameter("user") != null ? (String)request.getParameter("user") : (String)request.getParameter("uid"),
                                   (String)request.getParameter("profile"),
                                   (String)request.getParameter("language") != null ? (String)request.getParameter("language") : (String)request.getParameter("lang"),
                                   request.getParameter("mode") != null ? (String) request.getParameter("mode") : null,
                                   Double.parseDouble((String)request.getParameter("latitude")),
                                   Double.parseDouble((String)request.getParameter("longitude")),
                                   Double.parseDouble((String)request.getParameter("distance")),
                                   (String)request.getParameter("version"),
                                   (String)request.getHeader("user-agent"),
                                   request.getParameter("aroundme") != null ? (String) request.getParameter("aroundme") : null,
                                   request.getParameter("svd") != null ? (String) request.getParameter("svd") : null,
                                   request.getParameter("alreadyRecommended") != null ? (String) request.getParameter("alreadyRecommended") : null,
                                   request.getParameter("appID") != null ? (String) request.getParameter("appID") : null,
                                   request.getParameter("uid2") != null ? (String) request.getParameter("uid2") : null,
                                   null)); // do not give timestamp, this is not a simulation
}
else if(request.getParameter("action") != null && request.getParameter("action").equalsIgnoreCase("recommendForGroup") &&
   (request.getParameter("user") != null || request.getParameter("uid") != null) && 
   request.getParameter("profile") != null && 
   request.getParameter("group") != null && 
   (request.getParameter("language") != null || request.getParameter("lang") != null) && 
   request.getParameter("latitude") != null &&
   request.getParameter("longitude") != null &&
   request.getParameter("distance") != null) {
   String mode = request.getParameter("distance") != null ? (String) request.getParameter("distance") : null;
   out.write(Recommender.recommendForGroup((String)request.getParameter("user") != null ? (String)request.getParameter("user") : (String)request.getParameter("uid"), 
                                   (String)request.getParameter("profile"),
                                   (String)request.getParameter("group"),
                                   (String)request.getParameter("language") != null ? (String)request.getParameter("language") : (String)request.getParameter("lang"),
                                   request.getParameter("mode") != null ? (String) request.getParameter("mode") : null,
                                   Double.parseDouble((String)request.getParameter("latitude")),
                                   Double.parseDouble((String)request.getParameter("longitude")),
                                   Double.parseDouble((String)request.getParameter("distance")),
                                   (String)request.getParameter("version"),
                                   (String)request.getHeader("user-agent"),
                                   request.getParameter("svd") != null ? (String) request.getParameter("svd") : null,
                                   request.getParameter("appID") != null ? (String) request.getParameter("appID") : null,
                                   request.getParameter("uid2") != null ? (String) request.getParameter("uid2") : null,
                                   null)); // do not give timestamp, this is not a simulation
}
else if(request.getParameter("action") != null && request.getParameter("action").equalsIgnoreCase("dislike") &&
        (request.getParameter("user") != null || request.getParameter("uid") != null) && 
        request.getParameter("group") != null) {
   out.write(Recommender.dislike((String)request.getParameter("user") != null ? (String)request.getParameter("user") : (String)request.getParameter("uid"), (String)request.getParameter("group")));
}
else if(request.getParameter("action") != null && request.getParameter("action").equalsIgnoreCase("dislikeSubclass") &&
        (request.getParameter("user") != null || request.getParameter("uid") != null) && 
        request.getParameter("subclass") != null) {
   out.write(Recommender.dislikeSubclass((String)request.getParameter("user") != null ? (String)request.getParameter("user") : (String)request.getParameter("uid"), (String)request.getParameter("subclass")));
}
else if(request.getParameter("action") != null && request.getParameter("action").equalsIgnoreCase("removeDislike") &&
        (request.getParameter("user") != null || request.getParameter("uid") != null)) {
   out.write(Recommender.removeDislike((String)request.getParameter("user") != null ? (String)request.getParameter("user") : (String)request.getParameter("uid")));
}
else if(request.getParameter("action") != null && request.getParameter("action").equalsIgnoreCase("logViewedTweet") &&
        (request.getParameter("user") != null || request.getParameter("uid") != null) && 
        request.getParameter("twitterId") != null &&
        request.getParameter("group") != null) {
   out.write(Recommender.logViewedTweet((String)request.getParameter("user") != null ? (String)request.getParameter("user") : (String)request.getParameter("uid"),
                                        (String)request.getParameter("twitterId"),
                                        (String)request.getParameter("group")));
}
else if(request.getParameter("action") != null && request.getParameter("action").equalsIgnoreCase("getUserProfile") &&
        (request.getParameter("user") != null || request.getParameter("uid") != null)) {
   out.write(Recommender.getUserProfile((String)request.getParameter("user") != null ? (String)request.getParameter("user") : (String)request.getParameter("uid")));
}
else if(request.getParameter("action") != null && request.getParameter("action").equalsIgnoreCase("assess") &&
        request.getParameter("uid") != null && (request.getParameter("serviceUri") != null || request.getParameter("genID") != null) && request.getParameter("vote") != null &&
        request.getParameter("suggType") != null) {
out.write(Recommender.assess(request.getParameter("uid"), request.getParameter("serviceUri"), request.getParameter("genID"), request.getParameter("suggType"), request.getParameter("vote")));
}
/*else if(request.getParameter("action") != null && request.getParameter("action").equalsIgnoreCase("like") &&
        request.getParameter("user") != null && 
        request.getParameter("serviceURI") != null) {
   out.write(Recommender.like((String)request.getParameter("user"), (String)request.getParameter("serviceURI")));
}*/
/*<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>JSP Page</title>
    </head>
    <body>
        <h1>Hello World!</h1>
    </body>
</html>*/
%>
