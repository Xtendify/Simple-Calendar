## Contributing 

Simple Calendar is open source software. Community made patches, localizations, bug reports and contributions are always welcome and crucial to make this plugin a quality tool that empowers people, businesses and institutions alike.  

You can contribute to Simple Calendar by submitting pull requests or by opening issues here on GitHub.

If you are interested in translating Simple Calendar to your language, use the [WordPress.org translation area](https://translate.wordpress.org/projects/wp-plugins/google-calendar-events).

**Please Note:**

> GitHub is for *bug reports and contributions only*. If you have a support question or a request for a customization this is not the right place to post it. Use the [WordPress.org support forums](https://wordpress.org/support/plugin/google-calendar-events) instead.

### Reporting Issues

Reporting issues is a great way to became a contributor as it doesn't require technical skills. In fact you don't even need to know a programming language or to be able to check the code itself, you just need to make sure that everything works as expected and [submit an issue report](https://github.com/moonstonemedia/Simple-Calendar/issues) if you spot a bug. Sound like something you're up for? Go for it!

#### How To Submit An Issue Report

If something isn't working, congratulations you've found a bug! Help us fix it by submitting an issue report:

1. Isolate your issue, check for theme or plugin compatibility issues first.
2. Make sure you have a [GitHub account](https://github.com/signup/free).
3. Search the [Existing Issues](https://github.com/moonstonemedia/Simple-Calendar/issues) to be sure that the one you've noticed isn't already there.
4. Submit a report for your issue:
    * Clearly describe the issue.
    * Include steps to reproduce the issue if it's a bug.
    * If it's a compatibility issue, please add further details.

### Making Changes

If you are knowledgeable of PHP, JavaScript, HTML and CSS, and notice something that can be improved for the benefit of all users of this software, then you can propose your changes and issue a pull request (PR) here on GitHub.

#### How To Submit A Pull Request

First, fork this repository on GiHub or clone to your machine:

    $ git clone https://github.com/moonstonemedia/Simple-Calendar
    
This project uses [Composer](https://getcomposer.org/), to grab dependencies and have a working copy you will need to run:

    $ composer install
    $ composer dump-autoload -o

If you plan to work with stylesheets or JavaScript you may want to use Grunt as well:

    $ npm install

Make your changes locally, and push them to your forked repository.

Issue a pull request in the original repository with your remote branch (use the master branch as target, do not bother with other branches).

There are a few things to keep in mind when making changes and developing locally for this project.

* The most important one is to **ensure you stick to the [WordPress Coding Standards](http://make.wordpress.org/core/handbook/coding-standards/php/)**.
* When commiting reference your issue number (e.g. #1234) and include a note about the fix/changes you are proposing.
* Please **don't** modify the changelogs or readme.txt or other meta assets.  
