
from tests.testcase import TestCase
from sqlalchemy.engine.base import Engine
from edmunds.database.databasemanager import DatabaseManager


class TestMySql(TestCase):
    """
    Test MySql
    """

    def test_missing_params(self):
        """
        Test missing params
        :return:    void
        """

        config = [
            "from edmunds.database.drivers.mysql import MySql \n",
            "APP = { \n",
            "   'database': { \n",
            "       'enabled': True, \n",
            "       'instances': [ \n",
            "           { \n",
            "               'name': 'mysql',\n",
            "               'driver': MySql,\n",
            "               'user': 'root',\n",
            "               'pass': 'root',\n",
            "               'host': 'localhost',\n",
            "               'table': 'edmunds',\n",
            "           }, \n",
            "       ], \n",
            "   }, \n",
            "} \n",
        ]
        remove_lines = [8, 9, 10, 11]

        # Loop lines that should be individually removed
        for remove_line in remove_lines:
            new_config = config[:]
            del new_config[remove_line]

            self.write_config(new_config)

            # Create app
            app = self.create_application()

            # Error on loading of config
            with self.assert_raises_regexp(RuntimeError, 'missing some configuration'):
                app.database()

    def test_my_sql(self):
        """
        Test MySql
        :return:    void
        """

        # Write config
        self.write_config([
            "from edmunds.database.drivers.mysql import MySql \n",
            "APP = { \n",
            "   'database': { \n",
            "       'enabled': True, \n",
            "       'instances': [ \n",
            "           { \n",
            "               'name': 'mysql',\n",
            "               'driver': MySql,\n",
            "               'user': 'root',\n",
            "               'pass': 'root',\n",
            "               'host': 'localhost',\n",
            "               'table': 'edmunds',\n",
            "           }, \n",
            "       ], \n",
            "   }, \n",
            "} \n",
        ])

        # Create app
        app = self.create_application()

        # Test database
        engine = app.database()
        self.assert_is_not_none(engine)
        self.assert_is_instance(engine, Engine)