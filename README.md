# xrDebug

> 🔔 Subscribe to the [newsletter](https://chv.to/chevere-newsletter) to don't miss any update regarding Chevere.

<p align="center"><img alt="xrDebug" src="app/src/icon.svg" width="40%"></p>

https://user-images.githubusercontent.com/20590102/153045551-619c74cc-c4ae-41da-b7b6-bd8733e623a2.mp4

🦄 [View demo](https://user-images.githubusercontent.com/20590102/153045551-619c74cc-c4ae-41da-b7b6-bd8733e623a2.mp4)

[![Build](https://img.shields.io/github/actions/workflow/status/xrdebug/xrdebug/test.yml?branch=1.0&style=flat-square)](https://github.com/xrdebug/xrdebug/actions)![Code size](https://img.shields.io/github/languages/code-size/xrdebug/xrdebug?style=flat-square)
[![Apache-2.0](https://img.shields.io/github/license/xrdebug/xrdebug?style=flat-square)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-blueviolet?style=flat-square)](https://phpstan.org/)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fchevere%2Fxrdebug%2F1.0)](https://dashboard.stryker-mutator.io/reports/github.com/xrdebug/xrdebug/1.0)

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=chevere_xrdebug&metric=alert_status)](https://sonarcloud.io/dashboard?id=chevere_xrdebug)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=chevere_xrdebug&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=chevere_xrdebug)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=chevere_xrdebug&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=chevere_xrdebug)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=chevere_xrdebug&metric=security_rating)](https://sonarcloud.io/dashboard?id=chevere_xrdebug)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=chevere_xrdebug&metric=coverage)](https://sonarcloud.io/dashboard?id=chevere_xrdebug)
[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=chevere_xrdebug&metric=sqale_index)](https://sonarcloud.io/dashboard?id=chevere_xrdebug)
[![CodeFactor](https://www.codefactor.io/repository/github/xrdebug/xrdebug/badge)](https://www.codefactor.io/repository/github/xrdebug/xrdebug)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/66dc8822bf674a08a43054b9a3700626)](https://app.codacy.com/gh/xrdebug/xrdebug/dashboard)

[xrDebug](https://docs.xrdebug.com/) is a lightweight web application debug utility.

<p align="center">
    <img alt="xrDebug light" src=".screen/xr-0.1.3-light-welcome.png">
    <img alt="xrDebug dark" src=".screen/xr-0.1.3-dark-welcome.png">
</p>

## Documentation

Documentation available at [docs.xrdebug.com](https://docs.xrdebug.com/).

## Features

* Ephemeral, it doesn't store any persistent data
* Signed requests (Ed25519)
* End-to-end encryption (AES-GCM AE)
* Filter messages by Topics and Emotes
* Resume, Pause, Stop and Clear debug window controls
* Keyboard shortcuts (Resume **R**, Pause **P**, Stop **S** and Clear **C**)
* Re-name "xrDebug" session to anything you want
* Export dump output to clipboard or as PNG image
* Pause and resume your code execution
* Dark / Light mode follows your system preferences
* Portable & HTML based (save page, search, etc.)
* Uses [FiraCode](https://github.com/tonsky/FiraCode) font for displaying _beautiful looking dumps_ ™
* Open with editor links
* Responsive user interface

## PHP Features

* Configuration via code and `xr.php` file
* Dump arguments using [VarDump](https://chevere.org/packages/var-dump.html)
* Generates dump backtrace
* Custom inspectors
* Handle errors and exceptions (hook or replace your existing handler)

<p align="center">
    <img alt="xrDebug dark demo" src=".screen/xr-0.1.3-dark-demo.png">
</p>

## License

Copyright 2023 [Rodolfo Berrios A.](https://rodolfoberrios.com/)

Chevere is licensed under the Apache License, Version 2.0. See [LICENSE](LICENSE) for the full license text.

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
