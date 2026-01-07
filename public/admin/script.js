async function run(data)
{
    const json = JSON.stringify({
        authUser: document.querySelector("#authUser").value,
        authPass: document.querySelector("#authPass").value,
        ...data
    });

    const response = await fetch("/admin/run.php", {
        method: "POST",
        body: json
    });

    const output = document.querySelector("#cli-output");
    
    response.json().then((data) => {
        if (! response.ok) {
            output.innerHTML += '<p class="error">' + data.message + '</p>';
        } else {
            output.innerHTML += '<p>' + data.result + '</p>';
        }

        document.querySelector("#cli-output p:last-of-type").scrollIntoView({ behavior: "smooth" });
    });
}

async function cliCheck()
{
    const data = {
        command: "check"
    };

    run(data);
}

async function cliCronRun()
{
    const data = {
        command: "cron run"
    };

    run(data);
}

async function cliUpdatesList()
{
    const data = {
        command: "updates list all"
    };

    run(data);
}

async function cliUpdatesRun()
{
    const data = {
        command: "updates run all"
    };

    run(data);
}