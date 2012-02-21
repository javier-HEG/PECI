Copyright: (C) 2012, HEG - Haute École de Gestion de Genève
Author: Javier Belmonte <javier.belmonte@hesge.ch>
License: GNU/GPL v3.0 or later

This package implements an simplified intermediary interface to LimeSurvey.

LimeSurvey offers two main interfaces:

1. The interface for registered users, where surveys/groups/etc. are created.
2. The interface for surveyed users, where the only possibility is the
   participation in the survey.

Our intermediary interface is a third one which will be available for registered
users not having "SuperAdministrator" rights.

HOW IT WORKS

1. Install LimeSurvey v1.91+ (Rev. 12170 from their SVN SourceForge repository)
   https://limesurvey.svn.sourceforge.net/svnroot/limesurvey/source/limesurvey
2. Copy the "user" folder and its contents inside the LimeSurvey main folder,
   next to the "admin" folder.
3. Apply the patches in the "patches" folder.
