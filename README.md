# UnregisteredEditLinks extension

This is free software licensed under the GNU General Public License. Please
see http://www.gnu.org/copyleft/gpl.html for further details, including the
full text and terms of the license.

## Overview
An attempt at improving edit experience for anonymous users on [ARK Wiki](https://ark.wiki.gg).

By default when `edit` rights are restricted to a group, the `Edit` link in article navigation is replaced with `View source`,
which itself emits a protected page message with an unsuggestive message ("The action you have requested is limited to users in
the group: Users").

Altered behaviour checks for namespace edit restrictions and page protections, and if it's possible a freshly created user will
satisfy them, the `Edit` link is restored leading to the account creation form. The form itself displays a message notifying the
user why they landed there, and redirects back to the editor when successful.
