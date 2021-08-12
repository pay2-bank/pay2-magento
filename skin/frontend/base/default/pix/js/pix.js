
function startTimer(duration, display, approve_url) {
    var timer = duration, minutes, seconds;
    var completed = false;

    var statusCheckInterval = setInterval(function () {
        fetch(approve_url, {
            method: "GET"
        }).then(response => response.json()).then(json => {

            if (json.qrcode_expired) {
                reset()
            }

            if (json.payment_approved) {
                reset()
                setTimeout(() => {
                    var count = 10;
                    setInterval(() => {
                        if (count > 0)
                        display.textContent = `Aprovado... ${count}s`;
                        if (count == 0) {
                            if (document.body.classList.contains("sales-order-view")) {
                                window.location.reload();
                            } else {
                                window.location.href = '/sales/order/history/';
                            }
                        }
                        count--;
                    }, 1000);
                }, 800)

            }
        });
    }, 3000);

    var startTimerInterval = setInterval(function () {
        if (!completed) {
            minutes = parseInt(timer / 60, 10)
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;
        } else {
            clearInterval(startTimerInterval);
        }

        if (minutes == "00" && seconds == "00")
            completed = true

        display.textContent = minutes + ":" + seconds;

        if (--timer < 0) {
            timer = duration;
        }
    }, 1000);

    function reset() {
        clearInterval(statusCheckInterval);
        clearInterval(startTimerInterval);
        display.textContent = "00:00"
    }
}