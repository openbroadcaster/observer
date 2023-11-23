<html>
<head>
    <script type="text/javascript" src="script.js"></script>
</head>
<body>

<form onsubmit="return false;">
<div>
    <label>Debug Password</label>
    <input id="debug_password" type="text" value="" />
</div>
<div>
    <label>Player ID</label>
    <input id="player_id" type="number" value="" />
</div>
<div><button onclick="getSchedule();" id="submit">Submit</button></div>
</form>

<style>
form > div { display: flex; }
form > div > label { width: 150px; }
button { margin-top: 10px; }
table { border-collapse: collapse; margin-top: 30px; }
td,th { padding: 3px 5px; border: 1px solid #ccc; font-size: 12px; }
th { font-size: 12px; text-align: left; }
</style>

<div>Current Time: <span id="currentTime"></span></div>

<table>
<thead>
    <tr>
        <th>Gap (s)</th>
        <th>Start</th>
        <th>Artist</th>
        <th>Title</th>
        <th>Duration</th>
    </tr>
</thead>
<tbody id="result">

</tbody>
</table>

</body>
</html>
