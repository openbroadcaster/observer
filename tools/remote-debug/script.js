getSchedule = async function () {

    // set currentTime in UTC
    const currentTime = new Date().toUTCString();
    document.getElementById("currentTime").innerHTML = currentTime;

    // get player_id and debug_password values
    var player_id = document.getElementById("player_id").value;
    var debug_password = document.getElementById("debug_password").value;

    // async fetch /remote.php?player_id={id}&devmode={debug_password}&hbuffer=1&action=schedule    
    const response = await fetch(`/remote.php?id=${player_id}&devmode=${debug_password}&hbuffer=1&action=schedule`);
    const text = await response.text();
    const xml = new window.DOMParser().parseFromString(text, "text/xml");

    // clear #result
    document.getElementById("result").innerHTML = "";

    let current = null;
    let lastMediaEnd = null;

    // loop through each obconnect -> schedule -> show element
    xml.querySelectorAll("obconnect schedule show").forEach((show) => {

        const showDate = show.querySelector("date").innerHTML;
        const showTime = show.querySelector("time").innerHTML;

        current = new Date(showDate + 'T' + showTime + 'Z');

        const showTr = document.createElement("tr");
        const showTd = document.createElement("td");
        showTd.innerText = show.querySelector("name").innerHTML;
        showTd.setAttribute('colspan', 5);
        showTr.appendChild(showTd);
        document.getElementById("result").appendChild(showTr);

        // loop through each media element
        show.querySelectorAll("media item").forEach((media) => {

            // create a new div element
            const tr = document.createElement("tr");

            // create td for current
            const start = document.createElement("td");
            start.innerHTML = current.toUTCString();

            // create td's for artist, title, duration
            const gap = document.createElement("td");
            const artist = document.createElement("td");
            const title = document.createElement("td");
            const duration = document.createElement("td");

            // get gap between lastMediaEnd and current (only can happen during show changes)
            const gapMs = (current - lastMediaEnd) / 1000;

            // add data to tds
            gap.innerHTML = lastMediaEnd && gapMs ? gapMs : '';
            artist.innerHTML = media.querySelector("artist").innerHTML;
            title.innerHTML = media.querySelector("title").innerHTML;
            duration.innerHTML = media.querySelector("duration").innerHTML;

            // add tds to tr
            tr.appendChild(gap);
            tr.appendChild(start);
            tr.appendChild(artist);
            tr.appendChild(title);
            tr.appendChild(duration);

            // append the new tr element to #result
            document.getElementById("result").appendChild(tr);

            // add duration time to current
            current = new Date(current.getTime() + (duration.innerHTML * 1000));
            lastMediaEnd = current;
        });
    });

}
