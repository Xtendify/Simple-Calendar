## Contributing 

Simple Calendar is open source software. Community made patches, localizations, bug reports and contributions are always welcome and crucial to make this plugin a quality tool that empowers people, businesses and institutions alike.  

You can contribute to Simple Calendar by contributing translations, reporting issues, or submitting pull requests.

Support questions or feature requests should be posted to the [WordPress.org support forums](https://wordpress.org/support/plugin/google-calendar-events) instead.

### Contributing Translations

Interested in translating Simple Calendar to your language? Please use our [WordPress.org translation page](https://translate.wordpress.org/projects/wp-plugins/google-calendar-events) using a free account.

New to Translating WordPress? Read through the [Translator Handbook](https://make.wordpress.org/polyglots/handbook/tools/glotpress-translate-wordpress-org/) to get started.

It is no longer necessary to generate and translate .po and .mo files manually. If you want to become a translation editor for your language please get in touch.

### Reporting Issues

If something isn't working, you can help us fix it by submitting an issue report following these steps.

1. Isolate your issue, check for theme or plugin compatibility issues first.
2. Make sure you have a [GitHub account](https://github.com/signup/free).
3. Search the [Existing Issues](https://github.com/moonstonemedia/Simple-Calendar/issues) to be sure that the one you've noticed isn't already there.
4. Submit a report for your issue:
    * Clearly describe the issue.
    * Include steps to reproduce the issue if it's a bug.
    * If it's a compatibility issue, please add further details.

### Submitting Pull Requests

If you are knowledgeable of PHP, JavaScript, HTML and/or CSS, and you notice something that can be improved for the benefit of all users of this software, you can propose your changes and issue a pull request (PR) here on GitHub.

First, fork this repository on GiHub or clone to your machine:

    $ git clone https://github.com/moonstonemedia/Simple-Calendar
    
This project uses [Composer](https://getcomposer.org/) to grab dependencies not stored in source control. To setup composer run:

    $ composer install
    $ composer dump-autoload -o

If you plan to work with CSS or JavaScript you may want to use Grunt as well:

    $ npm install

First make your changes locally, then push them to your forked repository.

Next, issue a pull request in the original repository with your remote branch (use the master branch as target, do not bother with other branches).

Please review the [GitHub recommended guidelines for using pull requests](https://help.github.com/articles/using-pull-requests/).

There are a few things to keep in mind when making changes and developing locally for this project.

* The most important one is to **ensure you stick to the [WordPress Coding Standards](http://make.wordpress.org/core/handbook/coding-standards/php/)**.
* When commiting reference your issue number (e.g. #1234) and include a note about the fix/changes you are proposing.
* Please **don't** modify the changelogs or readme.txt or other meta assets.  

Finally, a big thanks from us and the plugin community for your help.
