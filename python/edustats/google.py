from hyper import HTTPConnection


def __parse_string(content, benchmark_string, start_string, end_string) -> str:
    '''
    Parse a string to return the value between start_string and end_string staring from benchmark_string
    :param content: the string to parse
    :param benchmark_string: search should start from this string
    :param start_string: the string before the target value
    :param end_string: the string after the target value
    :return: a string between start_string and end_string
    '''
    start_index = content.find(benchmark_string)
    start_index = content.find(start_string, start_index) + len(start_string)
    end_index = content.find(end_string, start_index)
    value = content[start_index:end_index]
    return value


def google_scholar(user: str = None) -> dict:
    '''
    This function returns the academic metrics of the given Google Scholar user
    :param user: This is the Google Scholar user (e.g. 7ftCdTQAAAAJ).
    This value can be extracted from the Google Scholar profile URL.
    :return: A dictionary with the total citations, h-Index and i10-Index values
    '''

    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    }
    conn = HTTPConnection('scholar.google.com:443')
    conn.request(method='GET',
                 url=f'/citations?user={user}&hl=en',
                 headers=headers)
    response = conn.get_response()
    if response.status == 200:
        content = response.read().decode('utf-8')
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

        result = {'citations': int(citations),
                  'h_index': int(h_index),
                  'i10_index': int(i10_index)}

    return result


# Unit Test
if __name__ == '__main__':
    google_user = "7ftCdTQAAAAJ"
    stats = google_scholar(google_user)
    print(stats)
