# Edu-Stats (Python Package)

This library parse and return the academic metrics such as number of citations, h-Index, etc.

Currently, this Python package supports the following source:

- Google Scholar

## Installation

This package can be installed:

- using `pip`:

```bash
pip install edu-stats
```

- using `Makefile` on a cloned/forked repo:

```bash
make install
```

- using `pip` on a cloned/forked repo:

```bash
pip install -e . --upgrade --upgrade-strategy only-if-needed
```

## Methods

- `edustats.google_scholar(user: str)` -> `dict`
<br />
It returns the academic metrics from Google Scholar.
  
  - `user: str` (mandatory): This is the Google Scholar user which can be retrieved from the Google Scholar profile URL.


## Example

```python
import edustats


google_user = "7ftCdTQAAAAJ"
stats = edustats.google_scholar(google_user)
print(stats)
```

## Developer(s)
Kaveh Bakhtiyari - [Website](http://bakhtiyari.com) | [Medium](https://medium.com/@bakhtiyari)
  | [LinkedIn](https://www.linkedin.com/in/bakhtiyari) | [GitHub](https://github.com/kavehbc)

## Contribution
Feel free to join the open-source community and contribute to this repository.

## Changelog

### 0.1.0 (24 Feb 2024) 
- Initial version
- Google Scholar is supported
  - Supported metrics: Total citation, h-Index, and i10-Index
