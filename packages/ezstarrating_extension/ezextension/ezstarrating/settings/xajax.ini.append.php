#?ini charset="iso-8859-1"?

# enable the activity indicator
[GeneralSettings]
ActivityIndicator=enabled

[ExtensionSettings]
# eZ publish extensions to look for xajax functions
ExtensionDirectories[]=starrating
# available xajax function files (without ".php")
AvailableFunctions[starrating]=starrating

[Flags]
#debug=true
#verbose=true
#statusMessages=true
#waitCursor=true
#scriptDeferral=true
#exitAllowed=true
#errorHandler=true
#cleanBuffer=true
#decodeUTF8Input=false
#outputEntities=false
#allowBlankResponse=false
#allowAllResponseTypes=false
#useUncompressedScripts=false

[DebugSettings]
# Deprecated
# Use [Flags]debug instead
#DebugAlert=disabled

[CompressionSettings]
# Deprecated
# Use [Flags]useUncompressedScripts instead
#UseUncompressedScripts=disabled
