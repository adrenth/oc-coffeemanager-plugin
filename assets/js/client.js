jQuery(document).ready(function () {
    // Enable pusher logging - don't include this in production
    Pusher.logToConsole = true;

    var pusher = new Pusher(COFFEE_MANAGER_PUSHER_AUTH_KEY, {
        cluster: COFFEE_MANAGER_PUSHER_CLUSTER,
        forceTLS: true
    });

    var channel = pusher.subscribe(COFFEE_MANAGER_PUSHER_CHANNEL);

    channel.bind('participant-initiates-new-round', function (data) {
        if (data.participant_id !== COFFEE_MANAGER_PARTICIPANT_ID) {
            showNotification(data.participant + ' initiated a new Coffee Round!');
        }
        refreshPartials('_session-actions,_round-details,_round-join');
    });

    channel.bind('participant-joined-round', function (data) {
        if (data.participant_id !== COFFEE_MANAGER_PARTICIPANT_ID) {
            showNotification(data.participant + ' joined the Coffee Round!');
        }
        refreshPartials('_round-details');
    });

    channel.bind('participant-left-round', function (data) {
        if (data.participant_id !== COFFEE_MANAGER_PARTICIPANT_ID) {
            showNotification('Unfortunately ' + data.participant + ' left the Coffee Round.');
        }
        refreshPartials('_round-details');
    });

    channel.bind('round-cancelled', function (data) {
        if (data.participant_id !== COFFEE_MANAGER_PARTICIPANT_ID) {
            showNotification('Coffee Round cancelled by ' + data.participant + '.');
        }
        refreshPartials('_session-actions,_round-details,_round-join');
    });

    channel.bind('round-expired', function () {
        showNotification('Coffee Round is expired.');
        refreshPartials('_session-actions,_round-details,_round-join');
    });

    channel.bind('round-finished', function (data) {
        if (data.participant_id !== COFFEE_MANAGER_PARTICIPANT_ID) {
            showNotification('Coffee Round finished by ' + data.participant + '.');
        }
        refreshPartials('_session-actions,_participant-details_round-details,_round-join');
    });

    channel.bind('participant-chosen', function (data) {
        if (data.participant_id !== COFFEE_MANAGER_PARTICIPANT_ID) {
            showNotification(data.participant + ' will get your beverage.');
        } else {
            showNotification('You\'re the designated participant!');
        }
        refreshPartials('_round-details,_round-join');
    });

    function showNotification(body) {
        var n = new Notification('Coffee Manager', {
            body: body
        });

        n.onclick = function () {
            $('form').focus();
        }
    }

    function refreshPartials(partialIds) {

        console.log(partialIds);

        $.request('coffeeManagerClient::onRefresh', {
            data: {
                partialIds: partialIds
            },
            success: function (data, textStatus, jqXHR) {
                this.success(data, textStatus, jqXHR);
            }
        });
    }
});
