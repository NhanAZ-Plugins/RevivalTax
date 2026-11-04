# RevivalTax

Charge a configurable EconomyAPI tax when players die on Axolotl-PM

[![CI](https://github.com/NhanAZ-Plugins/RevivalTax/actions/workflows/build.yml/badge.svg)](https://github.com/NhanAZ-Plugins/RevivalTax/actions/workflows/build.yml)
[![Latest release](https://img.shields.io/github/v/release/NhanAZ-Plugins/RevivalTax)](https://github.com/NhanAZ-Plugins/RevivalTax/releases/latest)
[![License](https://img.shields.io/github/license/NhanAZ-Plugins/RevivalTax)](https://github.com/NhanAZ-Plugins/RevivalTax/blob/master/LICENSE)
[![Discord](https://img.shields.io/discord/986553214889517088?label=Discord&logo=discord&logoColor=white&color=5865F2)](https://discord.gg/j2X83ujT6c)

## Overview

This plugin targets the PocketMine-MP and Axolotl-PM API 5 ecosystem and is maintained under [NhanAZ-Plugins](https://github.com/NhanAZ-Plugins).

## Requirements

- Axolotl-PM with PocketMine-MP API 5.0.0. Static analysis covers the 5.0.0 and 5.51.1 source revisions. The plugin is not certified against every release in the 5.x line.
- A PocketMine-MP 5 compatible [EconomyAPI](https://github.com/ACM-PocketMine-MP/EconomyAPI) plugin. The build workflow checks the API against a pinned source revision. EconomyAPI must be installed separately on the server.

## Configuration

Edit `plugins/RevivalTax/config.yml` after the first start when using the default plugin directory:

```yaml
taxPercent: 10
taxMessage: "§aYou have been deducted §e{taxMoney} §arevival tax! §f(§bTax Percent: §c{taxPercent}%§f)§r"
```

`taxPercent` must be a finite number from 0 through 100. The plugin refuses an invalid value at startup. It charges only when the balance is at least 1 and the calculated tax is positive. EconomyAPI rounds the requested deduction to two decimal places. `{taxMoney}` and `{taxPercent}` in `taxMessage` show the requested deduction and configured rate. The plugin sends the message only when EconomyAPI reports a successful deduction. A missing account or canceled transaction produces no charge message.

Back up the server's EconomyAPI data and this plugin's configuration before changing the tax rate on a live server.

## Build and validation

The [build workflow](.github/workflows/build.yml) tests the tax policy, runs PHPStan at maximum level, and uses [DevTools](https://github.com/NhanAZ/DevTools) to generate and validate the plugin PHAR. CI pins the exact source revisions used for reproducible checks. The workflow artifact contains the PHAR and build metadata; it does not bundle EconomyAPI.

## License

RevivalTax is distributed under [GPL-3.0](LICENSE).

## Contact

Join the [Discord server](https://discord.gg/j2X83ujT6c) for project discussion.
