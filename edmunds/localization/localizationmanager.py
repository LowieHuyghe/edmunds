
from edmunds.localization.localization.models.localization import Localization
from edmunds.localization.localization.models.number import Number
from edmunds.localization.localization.models.time import Time
from babel.core import Locale
from babel.dates import get_timezone
from edmunds.globals import request


class LocalizationManager(object):

    def __init__(self, app):
        """
        Constructor
        :param app: The app 
        """
        self._app = app

    def location(self, name=None, no_instance_error=False):
        """
        The location driver
        :param name:                The name of the session instance
        :type  name:                str
        :param no_instance_error:   Error when no instance
        :type  no_instance_error:   bool
        :return:                    A location driver
        :rtype:                     edmunds.localization.location.drivers.basedriver.BaseDriver
        """

        # Enabled?
        if not self._app.config('app.localization.location.enabled', False):
            return None

        # Return driver
        return self._app.extensions['edmunds.localization.location'].get(name, no_instance_error=no_instance_error)

    def localization(self, location=None):
        """
        Return localization
        :param location:    The location
        :type location:     geoip2.models.City
        :return: edmunds.localization.localization.models.localization.Localization
        """
        locale = self._locale(False)
        timezone = get_timezone(zone=location.location.time_zone)
        number = Number(locale)
        time = Time(locale, timezone)

        return Localization(locale, number, time)

    def _locale(self, from_supported_locales):
        """
        Get locale
        :param from_supported_locales:  Only return locale that is supported according to config
        :type from_supported_locales:   bool
        :return:                        Locale
        :rtype:                         babel.core.Locale
        """

        # List with all client locales
        browser_accept_locale_strings = self._get_browser_accept_locale_strings()
        user_agent_locale_strings = self._get_user_agent_locale_strings()
        fallback_locale_strings = self._get_fallback_locale_strings()
        preferred_locale_strings = browser_accept_locale_strings + user_agent_locale_strings + fallback_locale_strings

        # Only supported
        if not preferred_locale_strings:
            raise RuntimeError('No preferred locales to use, even with fallback!')
        elif from_supported_locales:
            supported_locales = self._app.config('app.localization.locale.supported', [])
            if not supported_locales:
                raise RuntimeError('No supported locales to use!')
            wanted_locale = Locale.negotiate(preferred_locale_strings, supported_locales, sep='_')
            if not wanted_locale:
                raise RuntimeError('Could not find supported locale even with fallback! (%s; %s; %s)' % (','.join(browser_accept_locale_strings), ','.join(user_agent_locale_strings), ','.join(fallback_locale_strings)))
        else:
            wanted_locale = preferred_locale_strings[0]

        # Process
        return Locale.parse(wanted_locale, sep='_')

    def _get_browser_accept_locale_strings(self):
        """
        Get browser accept locale strings
        :return:    list
        """
        # Accept Language
        browser_locales = request.accept_languages.values()
        browser_locales = list(map(self._normalize_locale, browser_locales))
        browser_locales = list(filter(lambda x: x, browser_locales))

        # Add to list
        preferred_locale_strings = browser_locales[:]

        # Add languages without territory as backup (de_DE -> de)
        for browser_locale in browser_locales:
            if '_' in browser_locale:
                browser_locale_language, = browser_locale.split('_')
                if browser_locale_language:
                    preferred_locale_strings.append(browser_locale_language)

        return preferred_locale_strings

    def _get_user_agent_locale_strings(self):
        """
        Get user agent locale strings
        :return:    list
        """
        # Make list
        preferred_locale_strings = []

        # User Agent
        user_agent_locale = request.user_agent.language
        user_agent_locale = self._normalize_locale(user_agent_locale)

        if user_agent_locale:
            # Add to list
            preferred_locale_strings.append(user_agent_locale)
            # Add language without territory as backup (de_DE -> de)
            if '_' in user_agent_locale:
                user_agent_language, = user_agent_locale.split('_')
                preferred_locale_strings.append(user_agent_language)

        return preferred_locale_strings

    def _get_fallback_locale_strings(self):
        """
        Get fallback locale strings
        :return:    void
        """

        # Make list
        preferred_locale_strings = []

        # Config Fallback
        config_fallback_locale = self._app.config('app.localization.locale.fallback', None)
        config_fallback_locale = self._normalize_locale(config_fallback_locale)
        if config_fallback_locale:
            # Add to list
            preferred_locale_strings.append(config_fallback_locale)
            # Add language without territory as backup (de_DE -> de)
            if '_' in config_fallback_locale:
                config_fallback_language, = config_fallback_locale.split('_')
                preferred_locale_strings.append(config_fallback_language)

        # Ultimate Fallback
        ultimate_fallback_locale = 'en_US'
        ultimate_fallback_locale = self._normalize_locale(ultimate_fallback_locale)
        if ultimate_fallback_locale:
            # Add to list
            preferred_locale_strings.append(ultimate_fallback_locale)
            # Add language without territory as backup (de_DE -> de)
            if '_' in ultimate_fallback_locale:
                ultimate_fallback_language, = ultimate_fallback_locale.split('_')
                preferred_locale_strings.append(ultimate_fallback_language)

        return preferred_locale_strings

    def _normalize_locale(self, locale_string):
        """
        Normalize locale
        :param locale_string:   Locale string
        :return:                Normalized locale string
        """
        if not locale_string:
            return None

        # Change separator and split
        locale_string = locale_string.replace('-', '_')
        locale_string_parts = locale_string.split('_')

        # Validate first part
        if not locale_string_parts[0]:
            return None
        # Lower case first part
        processed_locale_string_parts = [locale_string_parts[0].lower()]
        # Upper case other parts as long as parts are valid
        for locale_string_part in locale_string_parts[1:]:
            if not locale_string_part:
                break
            processed_locale_string_parts.append(locale_string_part.upper())

        # Join
        locale_string = '_'.join(processed_locale_string_parts)

        # Return
        return locale_string
