# Internetrix / Silverstripe Instagram

[![Build Status](link)]
[![Scrutinizer Code Quality](link)]
[![codecov](link)]
[![Version](link)]
[![License](link)]


## Introduction

This module provides an extension that can be used to generate, cache, and pull Instagram feed data. 

## Requirements
* SilverStripe CMS: ^4.0

## Installation

```
composer require internetrix/silverstripe-instagram
```

## Quickstart

Simply add this extension like so:

```
private static $extensions = [
    InstagramExtension::class
];
```
or
```
Your\Class\Here:
  extensions:
    - Internetrix\Instagram\Extensions\InstagramExtension
```

Once done, you'll be able to supply the username of an Instagram account. Upon saving, the account will be queried and
its feed data will be cached in a locally stored file. Your code can then call upon this data by using the 
`getInstagramPosts()` function.

## Automation

Do note that image links expire, and will need to be requeried. Schedule the Instagram Cache task to run daily to consistently
regenerate the image links as well as retrieve new posts.

To configure the Cache task to run through all feeds, add the following config:
```
Internetrix\Instagram\Extensions\InstagramExtension:
  extended_classes:
    - Your\Class\Here
```
The task will loop through every class included in the config and regenerate the their cache, if an Instagram 
username has been supplied.

## Fields

The following fields are requested as part of the `getInstagramPosts()` function:

* `ID`
* `Shortcode` (Can be used in links, e.g. https://instagram.com/p/$Shortcode)
* `Thumbnail` (The URL for the post thumbnail)
* `Owner` ID and Username 
* `Alt` (Image alt text)
* `Text` (The post's content)
* `Comments` (# of)
* `Likes` (# of)
* `Date` (Date posted in 'd F Y' format)
* `Type` (Hardcoded to Instagram, to allow merging with other feed arrays)

## TODO

* Implement proper error handling and reporting for User
