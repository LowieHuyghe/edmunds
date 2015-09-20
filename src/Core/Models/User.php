<?php

/**
 * LH Core
 *
 * The core of any web-project by Lowie Huyghe
 *
 * @author		Lowie Huyghe <iam@lowiehuyghe.com>
 * @copyright	Copyright (C) 2015, Lowie Huyghe. All rights reserved. Unauthorized copying of this file, via any medium is strictly prohibited. Proprietary and confidential.
 * @license		http://LicenseUrl
 * @since		Version 0.1
 */

namespace LH\Core\Models;

use Faker\Generator;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Collection;
use LH\Core\Database\Relations\BelongsToManyEnums;
use LH\Core\Helpers\ValidationHelper;

/**
 * The model of the user
 *
 * @author		Lowie Huyghe <iam@lowiehuyghe.com>
 * @copyright	Copyright (C) 2015, Lowie Huyghe. All rights reserved. Unauthorized copying of this file, via any medium is strictly prohibited. Proprietary and confidential.
 * @license		http://LicenseUrl
 * @since		Version 0.1
 *
 * @property int $id Database table-id
 * @property string $email Email of the user
 * @property string $password Password of the user
 * @property Collection $roles Roles for this user
 * @property string $locale Locale for this user
 *
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property Carbon deleted_at
 */
class User extends BaseModel implements AuthenticatableContract, CanResetPasswordContract
{
	use Authenticatable, CanResetPassword;

	/**
	 * The attributes that are mass assignable.
	 * @var array
	 */
	protected $fillable = ['email', 'password', 'locale'];

	/**
	 * The attributes excluded from the model's JSON form.
	 * @var array
	 */
	protected $hidden = ['password', 'remember_token'];

	/**
	 * Timestamps in the table
	 * @var bool|array
	 */
	public $timestamps = true;

	/**
	 * The class responsible for the roles
	 * @var string
	 */
	protected $roleClass;

	/**
	 * All the rights the user has
	 * @var Right[]
	 */
	private $rights;

	/**
	 * Roles belonging to this user
	 * @return BelongsToManyEnums
	 */
	public function roles()
	{
		if (!isset($this->roleClass))
		{
			throw new Exception('The class representing the Roles not set');
		}
		return $this->belongsToManyEnums($this->roleClass, 'user_roles');
	}

	/**
	 * Check if user has role
	 * @param $roleId
	 * @return bool
	 */
	public function hasRole($roleId)
	{
		return $this->roles()->contains($roleId);
	}

	/**
	 * Check if user has right
	 * @param $rightId
	 * @return bool
	 */
	public function hasRight($rightId)
	{
		//Fetch all the rights
		if (!isset($this->rights))
		{
			$rights = array();
			$roles = $this->roles;
			$roles->each(function($role) use (&$rights)
			{
				$roleRights = $role->rights;
				$roleRights->each(function($right) use (&$rights)
				{
					$rights[] = $right->id;
				});
			});
			$this->rights = array_unique($rights);
		}

		return in_array($rightId, $this->rights);
	}

	/**
	 * Add the validation of the model
	 * @param ValidationHelper $validator
	 */
	protected static function addValidationRules(&$validator)
	{
		$validator->integer('id');

		$validator->required('email');
		$validator->max('email', 255);
		$validator->unique('email', 'users');

		$validator->max('password', 60);

		$validator->max('remember_token', 100);

		$validator->date('created_at');
		$validator->date('updated_at');
	}

	/**
	 * Define-function for the instance generator
	 * @param Generator $faker
	 * @return array
	 */
	protected static function factory($faker)
	{
		return array(
			'email' => $faker->email,
			'password' => str_random(10),
			'remember_token' => str_random(10),
		);
	}

}
