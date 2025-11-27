# Changelog

All notable changes to this project will be documented in this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.6.0 (2025-11-27)

### Changed

*   Changed from parsing the entire content with DOMDocument to identifying iframe elements with a regular expression and just parsing those to avoid DOMDocument removing or modifying other elements.
