<?php declare(strict_types = 1);
/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


final class CComponentRegistry {

	private $components = [];

	/**
	 * Return registered component class instance.
	 *
	 * @param string $name  Component name.
	 *
	 * @return object
	 *
	 * @throws Exception when component with $name is not found.
	 */
	final public function get(string $name): object {
		if (!$this->has($name)) {
			throw new Exception(_s('Component %1$s is not registered.', $name));
		}

		return $this->components[$name];
	}

	/**
	 * Register component.
	 *
	 * @param string $name      Component name.
	 * @param object $instance  Component class instance.
	 *
	 * @throws Exception when name is already registered.
	 */
	final public function register(string $name, object $instance): void {
		if ($this->has($name)) {
			throw new Exception(_s('Component %1$s already registered.', $name));
		}

		$this->components[$name] = $instance;
	}

	/**
	 * Check if � component has been registered.
	 *
	 * @param string $name  Component name.
	 *
	 * @return bool
	 */
	final public function has(string $name): bool {
		return array_key_exists($name, $this->components);
	}

	/**
	 * Magic method to allow short syntax for the component instance get.
	 *
	 * @param string $name  Component name.
	 *
	 * @return object
	 *
	 * @throws Exception when component with $name is not found.
	 */
	final public function __get(string $name): object {
		return $this->get($name);
	}

	/**
	 * Magic method to allow short syntax for the component registration.
	 *
	 * @param string $name      Requested property name.
	 * @param object $instance  Component class instance.
	 *
	 * @throws Exception when name is already registered.
	 */
	final public function __set(string $name, object $instance): void {
		$this->register($name, $instance);
	}
}
