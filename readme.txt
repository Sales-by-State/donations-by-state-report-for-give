=== Donations by State Report for Give ===
Contributors: BusinessBloomer
Donate link: https://salesbystate.com/
Tags: donations-by-state, give, donations, analytics, fundraising
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

See a yearly breakdown of GiveWP donations by state / county / province for a given country, filterable by donation status.

== Description ==

Donations by State Report for Give adds a report showing donation totals grouped by state, county, or province, for a chosen year and a chosen set of donation statuses.

It appears under **Donations → Donations by State**. GiveWP managers can also rebuild the report table from **Donations → Tools → Donations by State**.

Use it when you need to know how much each state donated in a given year, counting only the donation statuses that matter for territory planning.

This plugin is a GiveWP extension. It requires [GiveWP](https://wordpress.org/plugins/give/) 3.0 or later. There are no settings screens to configure. After you activate the plugin, open the report and choose a country, year, and donation statuses.

Documentation: [salesbystate.com](https://salesbystate.com/)

= How to use =

1. Install and activate GiveWP, then install and activate this plugin.
2. Go to **Donations → Donations by State**.
3. Choose a **country**, a **year**, and the **donation statuses** that should count.
4. The table lists donations for every state in that country.

If the site already has donations, the plugin copies them into its report table in the background. A progress bar appears until that finishes. You can leave the page; the copy continues on its own.

= What the report shows =

* Donations for every state in the selected country
* A summary of that figure across all states
* Sortable columns and paginated results
* States with no donations, shown as zero rather than hidden

= Filters =

* **Country** — United States, Canada, and the United Kingdom. Defaults to the GiveWP base country.
* **Year** — a rolling list that starts ten years back and gains a year each January without dropping one. Defaults to the current year.
* **Donation status** — a checkbox list of GiveWP donation statuses. Defaults to Complete.

= How the figures are calculated =

GiveWP core does not store sales tax, VAT, or IRS withholding on a donation. The amount shown is the donation total GiveWP stores. Gateway fee recovery, when that add-on is used, is part of the donation total rather than a separate tax line.

Refunds are not modelled as separate records. A donation that has been refunded is controlled by the status filter.

= Performance =

Donations for a whole year are answered by one indexed query that returns one row per state. The response size does not grow with the number of donations.

= Data and privacy =

The plugin creates one custom database table holding, per donation: the donation ID, donation status, creation and completed dates, billing country and state codes, currency, and the donation, tax, shipping and net totals. It stores no names, addresses, email addresses or any other personal data.

Nothing is sent anywhere. The plugin makes no external HTTP requests, includes no third-party services, and collects no analytics or telemetry.

Deleting the plugin removes the table and its options.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/donations-by-state-report-for-give`, or install it through the Plugins screen.
2. Activate the plugin. GiveWP must already be installed and active.
3. Go to **Donations → Donations by State**.

On a site that already has donations, those donations are read into the report table once. This starts on its own. If it has not finished when you open the report, a progress bar shows how far along it is.

== Frequently Asked Questions ==

= The report shows zeros but I have donations. =

Your existing donations are still being read into the report table. Open the report and the progress bar will show how far along it is. It continues on its own; you can leave the page.

If only Complete is selected, tick any other statuses that should count.

= Which address does it group by? =

The billing address. GiveWP donations do not carry a shipping address.

= Are refunds deducted? =

The status filter decides whether a donation counts. GiveWP does not store a separate refund record against the original donation.

= Which date does the year filter use? =

The date the donation was completed, falling back to the date it was created for donations that were never completed.

= Why does the United Kingdom list fewer rows than the United States? =

GiveWP ships state lists for the United States and Canada, but not counties for the United Kingdom. UK donations still appear when they have a region stored. Zero-fill for unused counties is only available where GiveWP defines a list.

= Can I change the default donation status? =

Yes, with the `dbsgive_default_statuses` filter. GiveWP stores a complete donation as `publish`.

= Where can I get support? =

Use the [WordPress.org support forum](https://wordpress.org/support/plugin/donations-by-state-report-for-give/) for this plugin.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
