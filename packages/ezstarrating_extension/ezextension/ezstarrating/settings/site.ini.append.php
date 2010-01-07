<?php /* #?ini charset="utf-8"?

[TemplateSettings]
ExtensionAutoloadPath[]=ezstarrating

[RegionalSettings]
TranslationExtensions[]=ezstarrating
   

[eZStarRating]
# Avoid that users are allowed to rate content several times by
# logging in as different users. Done by storing content object
# attribute in a session variable (witch is kept even if you login/out)
UseUserSession=enabled

# Allows the user to change his rating after he has rated by returning to the page and rate again
AllowChangeRating=enabled

*/ ?>