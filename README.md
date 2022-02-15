# Edu-Stats
Official academic tracking platforms do not provide APIs to retrieve the relevant statistics such as h-Index, citations, etc.
Edu-Stats is a parser to extract the scholar statistics and can be used/presented on other websites (e.g. the personal/academic websites).
This API can be consumed as an embedded JavaScript, or retrieve the details in JSON format.

> **NOTE:** This code is open-source, and it can be used for non-commercial purposes.

## Live Demo
For live demo, you can check https://edu-stats.kaveh.me/demo/

## Supported Profiles
Google Scholar, Scopus, ResearcherID and Mendeley

> :warning: ResearchGate function has been removed.

## PHP Edition
In order to use the PHP edition, make sure to enable `php_curl.dll` and `php_openssl.dll` extensions in `php.ini`. 

## Legal Disclaimer
The end-user of this code should read the terms and conditions of the host provider (e.g. Google Scholar, Scopus, etc.), which he/she is trying to scrap its data, and they should follow their terms and coditions. The developer(s) and contributor(s) of this code do(es) not take any resposibility for misusing this code.
The end-users should monitor their requests to the servers in order to prevent any unnecessary traffic/attack on the target websites.
Access to the hosted JavaScript on this website is restricted to prevent generation of high traffic on the target websites.

## Known Issues
Scopus function does not work.


## Edu-Stats Supported Profiles
* Google Scholar
  * h-Index
  * i10-Index
  * Total Citations
* Scopus
  * Total Documents
  * h-Index
  * Co-Authors
  * Citation Documents
* Publons
  * Average Per Item
  * Average Per Year
  * h-Index
  * Times Cited
  * Number of Publications in WosCc

## Usage
The script generator should be placed in the `HEAD` section of the `HTML` code as below:

```html
<script src="/query/?google=<GOOGLE_ID>
                          &scopus=<SCOPUS_ID>
                          &publons=<PUBLONS_ID>
                          &prefix=<OPTIONAL_PREFIX>
                          &output=<javascript|json>
"></script>
```

The above code receives 3 optional query string parameters as below.
At least one of the parameters should be set.

| **Parameter** | **Type**     | **Description**                                                                                                                                                                                                                           | **Sample**                    |
|-----------|----------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------|
| google    | optional | Google Scholar ID<br />In your profile URL (http://scholar.google.com/citations?user=7ftCdTQAAAAJ&hl=en), `user` value is the Profile ID.                                                                                          | google=7ftCdTQAAAAJ       |
| scopus    | optional | Scopus Author ID<br />In your profile URL (http://www.scopus.com/authid/detail.url?authorId=54413825200), `authorId` value is the Profile ID.                                                                                              | scopus=54413825200        |
| publons      | optional | publons<br />In your profile URL (https://publons.com/researcher/1666350), the last part of the URL is the id.                                                                                                                   | publons=1666350          |
| prefix    | optional | A prefix to be used for variables to differentiate different scholars.<br />**LIMIT:** 10 alphanumeric characters.                                                                                                                             | prefix=asd123             |
| output    | optional | **javascript** (*Default*):<br />This parameter will generate the output in javascript variables.<br /><br />**json**:<br />This parameter generates JSON output for easier parsing, which is recommended for AJAX functions. JSON does not support prefix parameters. | output=json               |

### Prefix
If `prefix` is set, its value will be added to the begining of all variable names. This can be set to prevent variable similarities (duplicated names) in JavaScript output, specially, when there are multiple profile calls.
For instance, if the prefix has been set to `123abc`, then Google h-Index variable would be changed as follows:

``` javascript
document.write (123abc_Google_hIndex);
```

## Changelog
#### v2.0.1: 15 February 2022
Google Scholar bug fix
Publons added
ResearcherID and Mendeley removed

#### v2.0.0: 13 February 2018
Mendeley parser added.
Scopus function and variables rewritten.

#### v1.9.1: 24 April 2017
JSON output is added.

#### v1.9: 20 April 2017
PHP version is developed.
ResearchGate parser is removed.
Google Scholar bug is fixed.
Scopus bug is fixed.

#### v1.8: 8 April 2015
ResearchGate bug is fixed.

#### v1.7: 11 January 2015
ResearcherID parser is added.

#### v1.6: 10 January 2015
SSL (HTTPS) bug is fixed on Google Scholar.

#### v1.5: 6 January 2015
Prefix attribute is added for multi-extraction.

#### v1.0: 2 January 2015
Initial version

___
## Developer(s)
Kaveh Bakhtiyari - [Website](http://bakhtiyari.com) | [Medium](https://medium.com/@bakhtiyari)
  | [LinkedIn](https://www.linkedin.com/in/bakhtiyari) | [Github](https://github.com/kavehbc)

## Contribution
Feel free to join the open-source community and contribute to this repository.
