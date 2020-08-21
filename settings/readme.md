# Why?
Right now we cannot have a git repositry because localhost testing is impossible.
To get localhost testing up and running we need to make the web site have settings pointing to localhost and not the live site.
So we need a single file that contains all server information and not many places with hard coded strings.

# Procedure:

1. create settings.json file
2. copy all hard coded keys into json file.
3. link all files to use json file instad of the hard coded stuff.
4. make a git repositry and git ignore settingsfile. (can even be pushed to public github since no credentials are included)
5. Offline testing will now be possible by simply changing one variable.

# .json structure
3 settins options:
- *Debugging* (show all warnings, do not send mail with if error occours, use dummy server not live sql)
- *Live* (do not show warnings and errors, send mail when error occours, use live server)
- *develop* (show all warnings and errors, do not send mail when errors occour, use dummy server)


