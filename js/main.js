var urlToGetOpenPlayerBugs = "https://api.github.com/repos/openbroadcaster/obplayer/issues?state=open&labels=bug";
var urlToGetOpenServerBugs = "https://api.github.com/repos/openbroadcaster/observer/issues?state=open&labels=bug";
var urlToGetOpenPlayerSecurity = "https://api.github.com/repos/openbroadcaster/obplayer/issues?state=open&labels=security";
var urlToGetOpenServerSecurity = "https://api.github.com/repos/openbroadcaster/observer/issues?state=open&labels=security";


$(document).ready(function () {
        $.getJSON(urlToGetOpenPlayerSecurity, function (allIssues) {
        if (allIssues.length ==0) $(ply_security).append("No OBPLayer Security Issues</br>");
        $.each(allIssues, function (i, issue) {
            $(ply_security)
                .append("<b>" + issue.number + " - " + issue.title + "</b></br>")
//                .append("created at: " + issue.created_at + "</br>")
//                .append(issue.body + "</br></br></br>");
        });
    });

    $.getJSON(urlToGetOpenServerSecurity, function (allIssues) {
        if (allIssues.length ==0) $(srv_security).append("No OBServer Security Issues</br>");
        $.each(allIssues, function (i, issue) {
            $(srv_security)
                .append("<b>" + issue.number + " - " + issue.title + "</b></br>")
//                .append("created at: " + issue.created_at + "</br>")
//                .append(issue.body + "</br></br></br>");
        });
    });
    
    $.getJSON(urlToGetOpenPlayerBugs, function (allIssues) {
        if (allIssues.length ==0) $(ply_bugs).append("No open issues</br>");
        $.each(allIssues, function (i, issue) {
            $(ply_bugs)
                .append("<b>" + issue.number + " - " + issue.title + "</b></br>")
//                .append("created at: " + issue.created_at + "</br>")
//                .append(issue.body + "</br></br></br>");
        });
    });

    $.getJSON(urlToGetOpenServerBugs, function (allIssues) {
        if (allIssues.length ==0) $(srv_bugs).append("No open issues</br>");
        $.each(allIssues, function (i, issue) {
            $(srv_bugs)
                .append("<b>" + issue.number + " - " + issue.title + "</b></br>")
//                .append("created at: " + issue.created_at + "</br>")
//                .append(issue.body + "</br></br></br>");
        });
    });
    $('[data-toggle="tooltip"]').tooltip();
});

