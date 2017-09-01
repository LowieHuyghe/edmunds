
from edmunds.foundation.patterns.manager import Manager
from edmunds.localization.translations.drivers.configtranslator import ConfigTranslator


class TranslationsManager(Manager):
    """
    Translations Manager
    """

    def __init__(self, app):
        """
        Initiate the manager
        :param app:     The application
        :type  app:     Application
        """

        super(TranslationsManager, self).__init__(app, app.config('app.localization.translations.instances', []))

    def _create_local_translator(self, config):
        """
        Create Local Translator
        :param config:  The config
        :type  config:  dict
        :return:        Driver
        :rtype:         edmunds.localization.translations.drivers.maxmindcitydatabase.MaxMindCityDatabase
        """

        return ConfigTranslator()