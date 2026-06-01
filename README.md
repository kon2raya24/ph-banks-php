# phdevutils/banks

[![Packagist Version](https://img.shields.io/packagist/v/phdevutils/banks?label=Packagist&color=f28d1a&logo=packagist&logoColor=white)](https://packagist.org/packages/phdevutils/banks)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Philippine **bank & e-money registry** for PHP — SWIFT/BIC codes plus **InstaPay / PESONet** participation flags for universal & commercial banks (incl. foreign branches), thrift & rural banks, digital banks, and e-wallets (GCash, Maya, GrabPay, ShopeePay…). PHP companion to the npm package `@ph-dev-utils/banks`.

```bash
composer require phdevutils/banks
```

## Quick start

```php
use PhDevUtils\Banks\Banks;

Banks::findBank('BDO');            // ['name' => 'BDO Unibank, Inc.', 'shortName' => 'BDO', 'swift' => 'BNORPHMM', ...]
Banks::findBank('metrobank')['swift'];        // 'MBTCPHMM'
Banks::findBySwift('UBPHPHMMXXX')['shortName']; // 'UnionBank' (11-char input → 8-char head-office BIC)

Banks::listBanks(['type' => 'ewallet']);       // GCash, Maya, GrabPay, ShopeePay, ...
Banks::listBanks(['instapay' => true]);        // InstaPay participants
Banks::listBanks(['type' => 'universal_commercial', 'foreign' => false]); // domestic U/KBs

Banks::validateBIC('BNORPHMM');    // true
Banks::validateBIC('CITIPHMXV');   // false (9 chars — malformed)
Banks::parseBIC('BOPIPHMM');       // ['institution' => 'BOPI', 'country' => 'PH', 'location' => 'MM', 'branch' => null]
```

## API

| Method | Returns |
| --- | --- |
| `Banks::listBanks($filter = [])` | `array` of banks — filter by `type` / `instapay` / `pesonet` / `foreign` / `hasSwift` |
| `Banks::findBank($query)` | bank `array` or `null` — by SWIFT, exact/partial name, or short name |
| `Banks::findBySwift($code)` | bank `array` or `null` — by head-office BIC (8- or 11-char input) |
| `Banks::validateBIC($code)` | `bool` — ISO 9362 format (8 or 11 chars), case-insensitive |
| `Banks::parseBIC($code)` | `array{institution,country,location,branch}` or `null` |
| `Banks::participationAsOf()` | `string` — the BSP lists' as-of date (`YYYY-MM-DD`) |

Each bank array: `name`, `shortName`, `type` (`universal_commercial`/`thrift`/`rural`/`digital`/`ewallet`), `foreign` (bool), `swift` (string|null), `swiftConfidence` (`verified`/`single-source`/null), `instapay` (bool), `instapayReceiverOnly` (bool), `pesonet` (bool).

## Data & disclaimer

- **InstaPay / PESONet** flags come from the BSP-published ACH participant lists (BancNet & Philippine Clearing House Corporation) and reflect the as-of date — participation changes over time.
- **SWIFT/BIC** codes are head-office codes compiled from public directories. **Always confirm the exact code with the bank or recipient before initiating a wire transfer.** `swiftConfidence` is `verified` (2+ public directories agree) or `single-source`.

Not affiliated with BSP, PDIC, PCHC, BancNet, or SWIFT.

## License

MIT.
