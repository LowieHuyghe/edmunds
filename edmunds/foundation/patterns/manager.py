
import edmunds.support.helpers as helpers
from threading import Lock


class Manager(object):
	"""
	Manager
	"""

	def __init__(self, app, instances_config):
		"""
		Initiate the manager
		:param app: 				The application
		:type  app: 				Edmunds.Application
		:param instances_config:	Config for the instances
		:type  instances_config:	list
		"""

		self._app = app
		self._instances_config = instances_config
		self._instances = None
		self._extend = {}
		self._load_lock = Lock()


	def get(self, name = None):
		"""
		Get the instance
		:param name: 	The name of the instance
		:type  name:	str
		:return:		The driver
		:rtype:			BaseDriver
		"""

		self._load()

		if len(self._instances) == 0:
			raise RuntimeError('No instances declared.')

		if name is None:
			name = self._instances.keys()[0]

		return self._instances[name]


	def all(self):
		"""
		Get all the instances
		"""

		self._load()

		return self._instances.values()


	def _load(self):
		"""
		Load all the instances
		"""

		if self._instances is not None:
			return
		with self._load_lock:
			if self._instances is not None:
				return

		instances = {}

		for instance_config in self._instances_config:
			name = instance_config['name']
			if name in instances:
				raise RuntimeError('Redeclaring instance with name "%s"' % name)

			instances[name] = self._resolve(name)

		self._instances = instances


	def _reload(self):
		"""
		Reload the instances config
		"""

		with self._load_lock:
			self._instances = None


	def _resolve(self, name):
		"""
		Resolve the instance
		:param name:	The name of the instance
		:type  name:	str
		:return:		The driver
		:rtype:			BaseDriver
		"""

		# Pick one
		instance_config = None
		for instances_config_item in self._instances_config:
			if instances_config_item['name'] == name:
				instance_config = instances_config_item
				break

		# Check if there is one
		if instance_config is None:
			raise RuntimeError('There is no instance declared in the config with name "%s"' % name)

		# Make the driver
		driver_class = instance_config['driver']
		if driver_class in self._extend:
			driver = self._extend[driver_class](self._app, instance_config)
		else:
			method_name = '_create_%s' % helpers.snake_case(driver_class.__name__)
			driver = getattr(self, method_name)(instance_config)

		return driver


	def extend(self, driver_class, create_function):
		"""
		Extend the manager
		:param driver_class:		The class of the driver
		:type  driver_class:		class
		:param create_function:		The create function
		:type  create_function:		callable
		"""

		self._extend[driver_class] = create_function

		self._reload()