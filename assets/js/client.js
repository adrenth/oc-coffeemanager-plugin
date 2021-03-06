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
            showNotification(data.participant + ' heeft een koffierondje gestart!');
        }
        refreshPartials('_session-actions,_round-details,_round-join');
    });

    channel.bind('participant-joined-round', function (data) {
        if (data.participant_id !== COFFEE_MANAGER_PARTICIPANT_ID
            && data.participants.indexOf(COFFEE_MANAGER_PARTICIPANT_ID) !== -1) {
            showNotification(data.participant + ' doet mee!');
        }
        refreshPartials('_round-details');
    });

    channel.bind('participant-left-round', function (data) {
        if (data.participant_id !== COFFEE_MANAGER_PARTICIPANT_ID
            && data.participants.indexOf(COFFEE_MANAGER_PARTICIPANT_ID) !== -1) {
            showNotification(data.participant + ' heeft het rondje verlaten.');
        }
        refreshPartials('_round-details');
    });

    channel.bind('round-cancelled', function (data) {
        if (data.participant_id !== COFFEE_MANAGER_PARTICIPANT_ID
            && data.participants.indexOf(COFFEE_MANAGER_PARTICIPANT_ID) !== -1) {
            showNotification(data.participant + ' heeft het rondje geannuleerd.');
        }
        refreshPartials('_session-actions,_round-details,_round-join');
    });

    channel.bind('round-expired', function () {
        showNotification('Koffierondje is verlopen.');
        refreshPartials('_session-actions,_round-details,_round-join');
    });

    channel.bind('round-finished', function (data) {
        if (data.participant_id !== COFFEE_MANAGER_PARTICIPANT_ID
            && data.participants.indexOf(COFFEE_MANAGER_PARTICIPANT_ID) !== -1) {
            showNotification(data.participant + ' heeft zijn score verhoogd!');
        }

        refreshPartials('_session-actions,_participant-details,_round-details,_round-join');
    });

    channel.bind('round-finished-automatically', function () {
        showNotification('Het koffierondje is automatisch afgerond.');
        refreshPartials('_session-actions,_participant-details,_round-details,_round-join');
    });

    channel.bind('participant-chosen', function (data) {
        if (data.participant_id !== COFFEE_MANAGER_PARTICIPANT_ID) {
            if (data.participants.indexOf(COFFEE_MANAGER_PARTICIPANT_ID) !== -1) {
                showNotification(data.participant + ' gaat je drankje halen.');
            }
        } else {
            showNotification('Jij mag de drankjes gaan halen!');
        }
        refreshPartials('_round-details,_round-join');
    });

    var $clock = $('#clock');
    itsTheFinalCountDown($clock);

    $('#round-details').on('ajaxUpdate', function () {
        var $clock = $('#clock');
        itsTheFinalCountDown($clock);
    });

    function itsTheFinalCountDown(clock) {
        clock.countdown(clock.data('date'), function (event) {
            $(this).html(event.strftime('%-T'));
        });
    }

    function showNotification(body) {
        var n = new Notification('Coffee Manager', {
            body: body,
        });

        n.onclick = function () {
            window.focus();
            this.close();
        }
    }

    function refreshPartials(partialIds) {
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
