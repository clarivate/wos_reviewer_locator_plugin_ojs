# OJS Web of Science Reviewer Locator plugin
Developed and maintained by: Clarivate with the support from PLANet Systems Group.

## About
OJS plugin that adds Web of Science Reviewer Locator functionality to OJS hosted journals with active Reviewer Locator subscription.
You can read more about the Reviewer Locator here: https://clarivate.com/academia-government/scientific-and-academic-research/publisher-solutions/web-of-science-reviewer-locator/

## License
This plugin is licensed under the GNU General Public License v3.

### System Requirements
- OJS 3.1 - 3.3 (work is underway to release support of later versions)

### Installation
To install the plugin:
 - Download the `tar.gz` plugin file from https://github.com/clarivate/wos_reviewer_locator_plugin_ojs/releases
 - On your OJS site go to Settings > Website > Plugins > Upload a New Plugin, select the file you downloaded and click "Save"
 - Enable the plugin by going to:  Settings > Website > Plugins > Installed Plugins and ticking "ENABLE" for the "Web of Science Reviewer Locator Plugin"
 - Set up correct credentials in the "Connection" tab under plugin
   - Enter the API key provided for your subscription by Clarivate.
   - Choose the number of recommendations to display in the search results (default 30).

### Usage
For the plugin to work, the journal should have an active subscription to the Web of Science Reviewer Locator. Please see information about purchasing this service [here](https://clarivate.com/academia-government/scientific-and-academic-research/publisher-solutions/web-of-science-reviewer-locator/), or <a href="mailto:reviewservices@clarivate.com">email us</a> with your questions.


When the plugin is enabled, a new section "Web of Science Reviewer Locator" is added to the Review tab of the submission evaluation process. Use the "search" button to search Web of Science database for the suitable reviewers for a given article. Once the search returns recommended reviewers, the results are then saved against the submission and will be loaded from the system with subsequent navigation to the Review tab of that submission. The search results that are older than 60 days are deemed out of date and will be removed, so a new search can be made.

### Contact
For enquiries regarding usage, support, bugfixes, or comments please email:
reviewservices@clarivate.com
