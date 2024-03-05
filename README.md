# Spromonitor ##

## Plugin Monitoring for Moodle ##

**Developer:** Davide Mirra  
**Project Manager:** Claudia Giacomozzi  
**Project Name:** C4D  
**Project Link:** [Spromonitor Module Repository](https://github.com/emmedavide10/spromonitor.git)

## Project Overview ##

The Spromonitor Module, developed by Davide Mirra, is a crucial component for Claudia Giacomozzi's C4D project. This plugin provides advanced monitoring features within the Moodle environment, generating insightful line graphs based on specific input parameters.

## Description ##

The [surveypro module](https://github.com/kordan/moodle-mod_surveypro.git) has been enriched with a simple plotting tool, allowing the monitoring of parameters successively delivered by a compiler at different time points. It is especially useful when a survey is continuously proposed to a compiler to follow the change of one or more parameters. For example, within an intervention on a patient aimed at reducing obesity, a survey might be administered weekly to collect body weight. At each survey submission, the patient (and the clinician) can see graphically the progress and the impact of the intervention on body weight.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/mod/spromonitor

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## Contributions ##

We welcome contributions to this project. If you encounter issues or have suggestions, please open an issue in the project's issue tracking system or directly contact the developer, Davide Mirra.

## License ##

This project is distributed under the terms of the GNU General Public License version 3 or later. For detailed information, refer to the included LICENSE file in this repository.

© 2024 Davide Mirra. All rights reserved.
