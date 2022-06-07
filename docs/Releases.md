# OpenBroadcaster Versioning & Releases

**Draft Version 1**

These versioning guidelines are effective beginning with OpenBroadcaster 5.2. 

## Version Numbers

OpenBroadcaster uses MAJOR.MINOR.PATCH version numbers. For example, in version 5.2.1, "5" is the major version, "2" is the minor version, and "1" is the patch version. Minor version increases indicate a new release, and patch version increases indicate any code changes without a new release. The completion and final stabilization of the API will bring about a major version change (to OpenBroadcaster 6), after which no API breaking changes will be allowed without a major version change.

## Development, Release, and Support Schedule

Beginning with version 5.2, OpenBroadcaster uses flexible time-based released dates. Release dates are not strictly defined, but are relative to when feature development occurs. Version 5.2.0 is created from the testing branch (with latest commit December 29, 2021). This version is feature-frozen with bug fixes only and is the recommended version for production environments. Version 5.3.0 is created to receive new features (including code refactoring).

- 5.2.0 is is feature frozen release and will receive bug fixes only.
- Once a version is feature-frozen, a new version is created (minor version number increase) as soon as a new feature is added (including code refactoring).
- Approximately 6 months after this new minor version is created, it will be feature-frozen. 6 months is a general guideline only and a more suitable time may be selected based on the state of OpenBroadcaster development.
- OpenBroadcaster will support feature-frozen versions for a minimum of one year.

### Example Schedule

The following chart shows current and upcoming versions and support durations. This is an example only; dates are approximate.

![](C:\Users\brook\OneDrive - PikaLabs\Work\OpenBroadcaster Release Chart.png)


| Version | Start Date | Feature Freeze | End-of-Life |
| ------- | ---------- | -------------- | ----------- |
| 5.2     |            | May 2022       | May 2023    |
| 5.3     | May 2022   | Nov 2022       | Nov 2023    |
| 5.4     | Nov 2022   | May 2023       | May 2024    |

## Git Branches

The git branches are "wip" (work in progress), "develop", and major.minor version numbers such as 5.2, 5.3, etc. The current branches are as follows:

- wip: works in progress including incomplete features or in a known-broken state
- develop: bleeding edge but not work in progress (frequently merged into latest version branch)
- 5.2: current feature-frozen branch, bug fixes only
- 5.3: current branch receiving new features

In future, there will also be version branches that are no longer supported (end-of-life).

## Additional Notes

- It is recommended that production environments not needing the latest features upgrade to the next version once it has been feature-frozen for 6 months. (For example, upgrade to version 5.3 in May 2023).
- Bug fixes during the last 6 months of version support may be limited to major, breaking, and security bugs only, so that development can be focused on newer versions.
- Release dates are flexible so we are able to tie development we're doing such as doing with funded client(s) then we can wait a month or something to get that done instead of splitting that dev between two releases.
