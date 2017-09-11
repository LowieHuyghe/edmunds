
from tests.testcase import TestCase
from edmunds.localization.translations.sentencefiller import SentenceFiller
from edmunds.localization.localization.models.localization import Localization
from edmunds.localization.localization.models.number import Number
from edmunds.localization.localization.models.time import Time
from babel.core import Locale
from babel.dates import get_timezone
from datetime import date, datetime, time


class TestSentenceFiller(TestCase):
    """
    Test the Sentence Filler
    """

    def test_fill_in(self):
        """
        Test fill in
        :return:    void
        """

        sentence_filler = SentenceFiller()
        utc_tz = get_timezone('UTC')

        data = [
            ('nl_BE', '', '', None, 'Europe/Brussels'),
            ('nl_BE', '', '', {}, 'Europe/Brussels'),
            # Params
            ('nl_BE', 'This is a sentence without any params.', 'This is a sentence without any params.', {}, 'Europe/Brussels'),
            ('nl_BE', 'This is a sentence with 1 param.', 'This is a sentence with {count} param.', {'count': 1}, 'Europe/Brussels'),
            ('nl_BE', 'The sum of 5 and 9 is 14.', 'The {operation} of {a} and {b} is {result}.', {'operation': 'sum', 'a': 5, 'b': 9, 'result': 14}, 'Europe/Brussels'),
            # Boolean
            ('nl_BE', 'Previous statement is True.', 'Previous statement is {bool}.', {'bool': True}, 'Europe/Brussels'),
            # Float
            ('nl_BE', 'Divide that by 4 and you get 3,5.', 'Divide that by 4 and you get {float}.', {'float': 3.5}, 'Europe/Brussels'),
            ('en', 'Divide that by 4 and you get 3.5.', 'Divide that by 4 and you get {float}.', {'float': 3.5}, 'Europe/Brussels'),
            ('nl_BE', 'A very large number is 1.345.687,512.', 'A very large number is {float}.', {'float': 1345687.512}, 'Europe/Brussels'),
            ('en', 'A very large number is 1,345,687.512.', 'A very large number is {float}.', {'float': 1345687.512}, 'Europe/Brussels'),
            # Integer
            ('nl_BE', 'Multiply that by 2 and you get 7.', 'Multiply that by 2 and you get {integer}.', {'integer': 7}, 'Europe/Brussels'),
            ('en', 'Multiply that by 2 and you get 7.', 'Multiply that by 2 and you get {integer}.', {'integer': 7}, 'Europe/Brussels'),
            ('nl_BE', 'A very large number is 1.345.687.', 'A very large number is {integer}.', {'integer': 1345687}, 'Europe/Brussels'),
            ('en', 'A very large number is 1,345,687.', 'A very large number is {integer}.', {'integer': 1345687}, 'Europe/Brussels'),
            # Date
            ('nl_BE', 'Next appointment is 7 jun. 1992.', 'Next appointment is {date}.', {'date': date(1992, 6, 7)}, 'Europe/Brussels'),
            ('en', 'Next appointment is Jun 7, 1992.', 'Next appointment is {date}.', {'date': date(1992, 6, 7)}, 'Europe/Brussels'),
            ('nl_BE', 'Next appointment is 7 jun. 1992.', 'Next appointment is {date}.', {'date': date(1992, 6, 7)}, 'Asia/Chongqing'),
            ('en', 'Next appointment is Jun 7, 1992.', 'Next appointment is {date}.', {'date': date(1992, 6, 7)}, 'Asia/Chongqing'),
            # DateTime
            ('nl_BE', 'Next appointment is 9 mei 1992 07:26:13.', 'Next appointment is {datetime}.', {'datetime': datetime(1992, 5, 9, 5, 26, 13, tzinfo=utc_tz)}, 'Europe/Brussels'),
            ('en', 'Next appointment is May 9, 1992, 7:26:13 AM.', 'Next appointment is {datetime}.', {'datetime': datetime(1992, 5, 9, 5, 26, 13, tzinfo=utc_tz)}, 'Europe/Brussels'),
            ('nl_BE', 'Next appointment is 9 mei 1992 13:26:13.', 'Next appointment is {datetime}.', {'datetime': datetime(1992, 5, 9, 5, 26, 13, tzinfo=utc_tz)}, 'Asia/Chongqing'),
            ('en', 'Next appointment is May 9, 1992, 1:26:13 PM.', 'Next appointment is {datetime}.', {'datetime': datetime(1992, 5, 9, 5, 26, 13, tzinfo=utc_tz)}, 'Asia/Chongqing'),
            # Time
            ('nl_BE', 'Next appointment is 05:26:13.', 'Next appointment is {time}.', {'time': time(5, 26, 13, tzinfo=utc_tz)}, 'Europe/Brussels'),
            ('en', 'Next appointment is 5:26:13 AM.', 'Next appointment is {time}.', {'time': time(5, 26, 13, tzinfo=utc_tz)}, 'Europe/Brussels'),
            ('nl_BE', 'Next appointment is 05:26:13.', 'Next appointment is {time}.', {'time': time(5, 26, 13, tzinfo=utc_tz)}, 'Asia/Chongqing'),
            ('en', 'Next appointment is 5:26:13 AM.', 'Next appointment is {time}.', {'time': time(5, 26, 13, tzinfo=utc_tz)}, 'Asia/Chongqing'),
            # Strings
            ('nl_BE', 'Divide that by 4 and you get 3.5.', 'Divide that by 4 and you get {float}.', {'float': '3.5'}, 'Europe/Brussels'),
            ('en', 'Divide that by 4 and you get 3.5.', 'Divide that by 4 and you get {float}.', {'float': '3.5'}, 'Europe/Brussels'),
            ('nl_BE', 'A very large number is 1345687.512.', 'A very large number is {float}.', {'float': '1345687.512'}, 'Europe/Brussels'),
            ('en', 'A very large number is 1345687.512.', 'A very large number is {float}.', {'float': '1345687.512'}, 'Europe/Brussels'),
            ('nl_BE', 'Multiply that by 2 and you get 7.', 'Multiply that by 2 and you get {integer}.', {'integer': '7'}, 'Europe/Brussels'),
            ('en', 'Multiply that by 2 and you get 7.', 'Multiply that by 2 and you get {integer}.', {'integer': '7'}, 'Europe/Brussels'),
            ('nl_BE', 'A very large number is 1345687.', 'A very large number is {integer}.', {'integer': '1345687'}, 'Europe/Brussels'),
            ('en', 'A very large number is 1345687.', 'A very large number is {integer}.', {'integer': '1345687'}, 'Europe/Brussels'),
        ]

        for locale_str, expected, given, params, time_zone_str in data:
            locale = Locale.parse(locale_str, sep='_')
            time_zone = get_timezone(time_zone_str)
            number = Number(locale)
            time_instance = Time(locale, time_zone)
            localization = Localization(locale, number, time_instance)

            self.assert_equal(expected, sentence_filler.fill_in(localization, given, params=params))

    def test_plural_function(self):
        """
        Test plural function
        :return:    void
        """

        sentence_filler = SentenceFiller()

        data = [
            ('nl_BE', 'I have got 0 eggs in my hand.', 'I have got --plural:{eggs}__{eggs} egg__{eggs} eggs-- in my hand.', {'eggs': 0}),
            ('nl_BE', 'I have got 1 egg in my hand.', 'I have got --plural:{eggs}__{eggs} egg__{eggs} eggs-- in my hand.', {'eggs': 1}),
            ('nl_BE', 'I have got 2 eggs in my hand.', 'I have got --plural:{eggs}__{eggs} egg__{eggs} eggs-- in my hand.', {'eggs': 2}),
            ('nl_BE', 'I have got 3 eggs in my hand.', 'I have got --plural:{eggs}__{eggs} egg__{eggs} eggs-- in my hand.', {'eggs': 3}),
            ('bo', 'I have got 0 egg in my hand.', 'I have got --plural:{eggs}__{eggs} egg-- in my hand.', {'eggs': 0}),
            ('bo', 'I have got 1 egg in my hand.', 'I have got --plural:{eggs}__{eggs} egg-- in my hand.', {'eggs': 1}),
            ('bo', 'I have got 2 egg in my hand.', 'I have got --plural:{eggs}__{eggs} egg-- in my hand.', {'eggs': 2}),
            ('bo', 'I have got 3 egg in my hand.', 'I have got --plural:{eggs}__{eggs} egg-- in my hand.', {'eggs': 3}),
            ('ar', 'I have got 0 egg in my hand.', 'I have got --plural:{eggs}__{eggs} egg__{eggs} eggs__{eggs} eggz__{eggs} eggk__{eggs} eggl__{eggs} eggo-- in my hand.', {'eggs': 0}),
            ('ar', 'I have got 1 eggs in my hand.', 'I have got --plural:{eggs}__{eggs} egg__{eggs} eggs__{eggs} eggz__{eggs} eggk__{eggs} eggl__{eggs} eggo-- in my hand.', {'eggs': 1}),
            ('ar', 'I have got 2 eggz in my hand.', 'I have got --plural:{eggs}__{eggs} egg__{eggs} eggs__{eggs} eggz__{eggs} eggk__{eggs} eggl__{eggs} eggo-- in my hand.', {'eggs': 2}),
            ('ar', 'I have got 3 eggk in my hand.', 'I have got --plural:{eggs}__{eggs} egg__{eggs} eggs__{eggs} eggz__{eggs} eggk__{eggs} eggl__{eggs} eggo-- in my hand.', {'eggs': 3}),
            ('ar', 'I have got 101 eggl in my hand.', 'I have got --plural:{eggs}__{eggs} egg__{eggs} eggs__{eggs} eggz__{eggs} eggk__{eggs} eggl__{eggs} eggo-- in my hand.', {'eggs': 101}),
            ('ar', 'I have got 11 eggo in my hand.', 'I have got --plural:{eggs}__{eggs} egg__{eggs} eggs__{eggs} eggz__{eggs} eggk__{eggs} eggl__{eggs} eggo-- in my hand.', {'eggs': 11}),
        ]

        for locale_str, expected, given, params in data:
            locale = Locale.parse(locale_str, sep='_')
            time_zone = get_timezone('Europe/Brussels')
            number = Number(locale)
            time_instance = Time(locale, time_zone)
            localization = Localization(locale, number, time_instance)
            self.assert_equal(expected, sentence_filler.fill_in(localization, given, params=params))
