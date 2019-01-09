define([
    'underscore',
    'orotranslation/js/translator'
], function(_, __) {
    'use strict';

    return {
        /** @property {Object} */
        templates: {
            reminderIcon: '<span class="reminder-status fa-bell-o" title="' + __('Reminders') + '"></span>',
            notRespondedIcon: '<span class="invitation-status fa-reply" title="' + __('Not responded') + '"></span>',
            tentativeIcon: '<span class="invitation-status fa-question-circle" title="' +
                __('Tentatively accepted') + '"></span>',
            acceptedIcon: '<span class="invitation-status fa-check" title="' + __('Accepted') + '"></span>'
        },

        decorate: function(eventModel, $el) {
            var $body = $el.find('.fc-content');
            var $timePlace = $el.find('.fc-time');
            var reminders = eventModel.get('reminders');
            var invitationStatus = eventModel.getInvitationStatus();
            // if $time is not displayed show related info into $body
            if (!$timePlace.length) {
                $timePlace = $body;
            }
            if (reminders && _.keys(reminders).length) {
                $el.prepend(this.templates.reminderIcon);
            } else {
                $el.find('.reminder-status').remove();
            }
            switch (invitationStatus) {
                case 'none':
                    $timePlace.prepend(this.templates.notRespondedIcon);
                    break;
                case 'accepted':
                    $timePlace.prepend(this.templates.acceptedIcon);
                    break;
                case 'tentative':
                    $timePlace.prepend(this.templates.tentativeIcon);
                    break;
                case 'declined':
                    $body.addClass('invitation-status-declined');
                    break;
                default:
                    $body.find('.invitation-status').remove();
                    $body.removeClass('invitation-status-declined');
                    eventModel._isInvitationIconAdded = false;
                    break;
            }
        }
    };
});
