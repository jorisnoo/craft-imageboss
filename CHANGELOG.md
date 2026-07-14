# Changelog

All notable changes to this project will be documented in this file.

## [1.1.0](https://github.com/jorisnoo/craft-imageboss/releases/tag/v1.1.0) (2026-07-14)

### Bug Fixes

- disable includeVolumeFolder by default ([9128e4b](https://github.com/jorisnoo/craft-imageboss/commit/9128e4b79befb44082df0b9684671a0dea7a7fc4))
- encode SVG placeholder data URIs to prevent srcset splitting ([6028380](https://github.com/jorisnoo/craft-imageboss/commit/60283800b343587320f3bf9e7d3ab0fcf4c8ef8b))

### Chores

- **deps:** bump actions/checkout from 6 to 7 ([93666f0](https://github.com/jorisnoo/craft-imageboss/commit/93666f089ecee05e81407ace65a06b2729990978))
## [1.0.3](https://github.com/jorisnoo/craft-imageboss/releases/tag/v1.0.3) (2026-06-02)

### Features

- add animation support for animated GIFs ([2aad90a](https://github.com/jorisnoo/craft-imageboss/commit/2aad90a982dc734d6a1d083be86c2d5f085006f7))
- **download:** support downloads without transform dimensions ([bb4cae7](https://github.com/jorisnoo/craft-imageboss/commit/bb4cae725f5587e3fe3b1882a28f663b6dcb8a31))
## [1.0.2](https://github.com/jorisnoo/craft-imageboss/releases/tag/v1.0.2) (2026-05-16)

### Features

- add download option to force browser downloads ([9e43c18](https://github.com/jorisnoo/craft-imageboss/commit/9e43c18659924f722e17bdb2f865a9092424bcc8))
- add compression option to cdn() method ([73b09ab](https://github.com/jorisnoo/craft-imageboss/commit/73b09aba5e800011e571c7a500187a13e929593a))

### Documentation

- document cache purging feature ([45637a0](https://github.com/jorisnoo/craft-imageboss/commit/45637a000c755b7d5f11edb820d3ded92b7b5157))
## [1.0.1](https://github.com/jorisnoo/craft-imageboss/releases/tag/v1.0.1) (2026-05-16)

### ⚠ BREAKING CHANGES

- remove imageSet() method for CSS image-set() backgrounds ([04f4c29](https://github.com/jorisnoo/craft-imageboss/commit/04f4c29b64be53d8a0f3e69f2ab5d41662434819))

### Features

- add cdn() method for passthrough URLs ([60b8540](https://github.com/jorisnoo/craft-imageboss/commit/60b8540166e51bbc86f1c718873c9baf1e3cabf9))
- remove imageSet() method for CSS image-set() backgrounds ([04f4c29](https://github.com/jorisnoo/craft-imageboss/commit/04f4c29b64be53d8a0f3e69f2ab5d41662434819))
- add imageSet() method for CSS image-set() responsive backgrounds ([ac72d5a](https://github.com/jorisnoo/craft-imageboss/commit/ac72d5a6584710062f5905e64be85c8e73b03b39))

### Chores

- add MIT license ([473aae9](https://github.com/jorisnoo/craft-imageboss/commit/473aae989f62d8add9db19a0a09c4d8e346bd381))
## [1.0.0](https://github.com/jorisnoo/craft-imageboss/releases/tag/v1.0.0) (2026-05-12)

### Features

- default focal point to center when asset has none ([cb36e20](https://github.com/jorisnoo/craft-imageboss/commit/cb36e20a4830926b52410b2364a91b150f4966b1))
- add quality parameter for image optimization ([5b52957](https://github.com/jorisnoo/craft-imageboss/commit/5b5295726a377a1bb66ba37ba4a23eb619808d2b))
- add automatic ImageBoss cache purge on asset replacement ([ec47f89](https://github.com/jorisnoo/craft-imageboss/commit/ec47f89fcdddc6ca947044aa6d0e066521601a3c))
- add release workflow and changelog management ([06f4271](https://github.com/jorisnoo/craft-imageboss/commit/06f4271406287439e2d678d3e9e39e24a664119a))

### Code Refactoring

- reduce duplication and extract url generation logic ([c8a44ac](https://github.com/jorisnoo/craft-imageboss/commit/c8a44ace302a14fbf04efd3d78de4f9de63c47ce))

### Documentation

- add README ([4092fdc](https://github.com/jorisnoo/craft-imageboss/commit/4092fdca880785e32f4c18ad1dce01f8785ff0d0))

### Chores

- remove version field from composer.json ([788b543](https://github.com/jorisnoo/craft-imageboss/commit/788b543b5451826dd0fc57713921f54ec7c9c607))
- upgrade to PHP 8.3 and Pest 4.0, rename config properties and refactor tests ([44e279d](https://github.com/jorisnoo/craft-imageboss/commit/44e279d7f996b8285edd087516c7e5372880730e))
