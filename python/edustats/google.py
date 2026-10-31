import re

import requests

GOOGLE_SCHOLAR_URL = 'https://scholar.google.com/citations'
# Google Scholar user IDs are short alphanumeric tokens (e.g. 7ftCdTQAAAAJ)
GOOGLE_USER_PATTERN = re.compile(r'[A-Za-z0-9_-]{1,32}')
REQUEST_TIMEOUT = 30  # seconds


def __parse_string(content, benchmark_string, start_string, end_string) -> str:
    '''
    Parse a string to return the value between start_string and end_string staring from benchmark_string
    :param content: the string to parse
    :param benchmark_string: search should start from this string
    :param start_string: the string before the target value
    :param end_string: the string after the target value
    :return: a string between start_string and end_string, or None if not found
    '''
    start_index = content.find(benchmark_string)
    if start_index == -1:
        return None
    start_index = content.find(start_string, start_index)
    if start_index == -1:
        return None
    start_index += len(start_string)
    end_index = content.find(end_string, start_index)
    if end_index == -1:
        return None
    value = content[start_index:end_index]
    return value


def __to_int(value):
    '''
    Convert a scraped value to int; the remote content is untrusted
    :param value: the string to convert
    :return: the integer value, or None if it is not a plain number
    '''
    if value is None:
        return None
    value = value.replace(',', '').strip()
    return int(value) if value.isascii() and value.isdigit() else None


def google_scholar(user: str = None) -> dict:
    '''
    This function returns the academic metrics of the given Google Scholar user
    :param user: This is the Google Scholar user (e.g. 7ftCdTQAAAAJ).
    This value can be extracted from the Google Scholar profile URL.
    :return: A dictionary with the total citations, h-Index and i10-Index values
    :raises ValueError: if user is not a valid Google Scholar user ID
    '''

    if not isinstance(user, str) or not GOOGLE_USER_PATTERN.fullmatch(user):
        raise ValueError(f'Invalid Google Scholar user: {user!r}')

    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    }
    # requests verifies TLS certificates by default and encodes the query parameters
    response = requests.get(GOOGLE_SCHOLAR_URL,
                            params={'user': user, 'hl': 'en'},
                            headers=headers,
                            timeout=REQUEST_TIMEOUT)
    if response.status_code == 200:
        content = response.text
    else:
        content = None

    if content is None:
        result = None
    else:
        # citations
        start_string = '<td class="gsc_rsb_std">'
        end_string = '</td>'
        benchmark_string = ''
        citations = __parse_string(content, benchmark_string, start_string, end_string)

        # h-Index
        start_string = '<td class="gsc_rsb_std">'
        end_string = '</td>'
        benchmark_string = "h-index</a>"
        h_index = __parse_string(content, benchmark_string, start_string, end_string)

        # i10-Index
        start_string = '<td class="gsc_rsb_std">'
        end_string = '</td>'
        benchmark_string = "i10-index</a>"
        i10_index = __parse_string(content, benchmark_string, start_string, end_string)

        result = {'citations': __to_int(citations),
                  'h_index': __to_int(h_index),
                  'i10_index': __to_int(i10_index)}

    return result


# Unit Test
if __name__ == '__main__':
    google_user = "7ftCdTQAAAAJ"
    stats = google_scholar(google_user)
    print(stats)
