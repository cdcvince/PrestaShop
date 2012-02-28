<?php
/*
* 2007-2012 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>o
*  @copyright  2007-2012 PrestaShop SA
*  @version  Release: $Revision: 7466 $
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class AdminModulesPositionsControllerCore extends AdminController
{
	private $display_key = 0;

	public function postProcess()
	{
		// Getting key value for display
		if (Tools::getValue('show_modules') && strval(Tools::getValue('show_modules')) != 'all')
			$this->display_key = (int)Tools::getValue('show_modules');

		// Change position in hook
		if (array_key_exists('changePosition', $_GET))
		{
			if ($this->tabAccess['edit'] === '1')
			{
				$id_module = (int)Tools::getValue('id_module');
				$id_hook = (int)Tools::getValue('id_hook');
				$module = Module::getInstanceById($id_module);
				if (Validate::isLoadedObject($module))
				{
					$module->updatePosition($id_hook, (int)Tools::getValue('direction'));
					Tools::redirectAdmin(self::$currentIndex.($this->display_key ? '&show_modules='.$this->display_key : '').'&token='.$this->token);
				}
				else
					$this->errors[] = Tools::displayError('module cannot be loaded');
			}
			else
				$this->errors[] = Tools::displayError('You do not have permission to edit here.');
		}

		// Add new module in hook
		elseif (Tools::isSubmit('submitAddToHook'))
		{
			if ($this->tabAccess['add'] === '1')
			{
				// Getting vars...
				$id_module = (int)Tools::getValue('id_module');
				$module = Module::getInstanceById($id_module);
				$id_hook = (int)Tools::getValue('id_hook');
				$hook = new Hook($id_hook);

				if (!$id_module || !Validate::isLoadedObject($module))
					$this->errors[] = Tools::displayError('module cannot be loaded');
				elseif (!$id_hook || !Validate::isLoadedObject($hook))
					$this->errors[] = Tools::displayError('Hook cannot be loaded.');
				elseif (Hook::getModulesFromHook($id_hook, $id_module))
					$this->errors[] = Tools::displayError('This module is already transplanted to this hook.');
				elseif (!$module->isHookableOn($hook->name))
					$this->errors[] = Tools::displayError('This module can\'t be transplanted to this hook.');
				// Adding vars...
				else
				{
					if (!$module->registerHook($hook->name, Shop::getContextListShopID()))
						$this->errors[] = Tools::displayError('An error occurred while transplanting module to hook.');
					else
					{
						$exceptions = Tools::getValue('exceptions');
						$exceptions = (isset($exceptions[0])) ? $exceptions[0] : array();
						$exceptions = explode(',', str_replace(' ', '', $exceptions));

						foreach ($exceptions as $except)
							if (!Validate::isFileName($except))
								$this->errors[] = Tools::displayError('No valid value for field exceptions');

						if (!$this->errors && !$module->registerExceptions($id_hook, $exceptions, Shop::getContextListShopID()))
							$this->errors[] = Tools::displayError('An error occurred while transplanting module to hook.');
					}

					if (!$this->errors)
						Tools::redirectAdmin(self::$currentIndex.'&conf=16'.($this->display_key ? '&show_modules='.$this->display_key : '').'&token='.$this->token);
				}
			}
			else
				$this->errors[] = Tools::displayError('You do not have permission to add here.');
		}

		// Edit module from hook
		elseif (Tools::isSubmit('submitEditGraft'))
		{
			if ($this->tabAccess['add'] === '1')
			{
				// Getting vars...
				$id_module = (int)Tools::getValue('id_module');
				$module = Module::getInstanceById($id_module);
				$id_hook = (int)Tools::getValue('id_hook');
				$hook = new Hook($id_hook);

				if (!$id_module || !Validate::isLoadedObject($module))
					$this->errors[] = Tools::displayError('module cannot be loaded');
				elseif (!$id_hook || !Validate::isLoadedObject($hook))
					$this->errors[] = Tools::displayError('Hook cannot be loaded.');
				else
				{
					$exceptions = Tools::getValue('exceptions');
					if (is_array($exceptions))
					{
						foreach ($exceptions as $id => $exception)
						{
							$exception = explode(',', str_replace(' ', '', $exception));

							// Check files name
							foreach ($exception as $except)
								if (!Validate::isFileName($except))
									$this->errors[] = Tools::displayError('No valid value for field exceptions');

							$exceptions[$id] = $exception;
						}

						// Add files exceptions
						if (!$module->editExceptions($id_hook, $exceptions))
							$this->errors[] = Tools::displayError('An error occurred while transplanting module to hook.');

						if (!$this->errors)
							Tools::redirectAdmin(self::$currentIndex.'&conf=16'.($this->display_key ? '&show_modules='.$this->display_key : '').'&token='.$this->token);
					}
					else
					{
						$exceptions = explode(',', str_replace(' ', '', $exceptions));

						// Check files name
						foreach ($exceptions as $except)
							if (!Validate::isFileName($except))
								$this->errors[] = Tools::displayError('No valid value for field exceptions');

						// Add files exceptions
						if (!$module->editExceptions($id_hook, $exceptions, Shop::getContextListShopID()))
							$this->errors[] = Tools::displayError('An error occurred while transplanting module to hook.');
						else
							Tools::redirectAdmin(self::$currentIndex.'&conf=16'.($this->display_key ? '&show_modules='.$this->display_key : '').'&token='.$this->token);
					}
				}
			}
			else
				$this->errors[] = Tools::displayError('You do not have permission to add here.');
		}

		// Delete module from hook
		elseif (array_key_exists('deleteGraft', $_GET))
		{
			if ($this->tabAccess['delete'] === '1')
			{
				$id_module = (int)Tools::getValue('id_module');
				$module = Module::getInstanceById($id_module);
				$id_hook = (int)Tools::getValue('id_hook');
				$hook = new Hook($id_hook);
				if (!Validate::isLoadedObject($module))
					$this->errors[] = Tools::displayError('module cannot be loaded');
				elseif (!$id_hook || !Validate::isLoadedObject($hook))
					$this->errors[] = Tools::displayError('Hook cannot be loaded.');
				else
				{
					if (!$module->unregisterHook($id_hook, Shop::getContextListShopID())
						|| !$module->unregisterExceptions($id_hook, Shop::getContextListShopID()))
						$this->errors[] = Tools::displayError('An error occurred while deleting module from hook.');
					else
						Tools::redirectAdmin(self::$currentIndex.'&conf=17'.($this->display_key ? '&show_modules='.$this->display_key : '').'&token='.$this->token);
				}
			}
			else
				$this->errors[] = Tools::displayError('You do not have permission to delete here.');
		}
		elseif (Tools::isSubmit('unhookform'))
		{
			if (!($unhooks = Tools::getValue('unhooks')) || !is_array($unhooks))
				$this->errors[] = Tools::displayError('Select a module to unhook.');
			else
			{
				foreach ($unhooks as $unhook)
				{
					$explode = explode('_', $unhook);
					$id_hook = $explode[0];
					$id_module = $explode[1];
					$module = Module::getInstanceById((int)$id_module);
					$hook = new Hook((int)$id_hook);
					if (!Validate::isLoadedObject($module))
						$this->errors[] = Tools::displayError('module cannot be loaded');
					elseif (!$id_hook || !Validate::isLoadedObject($hook))
						$this->errors[] = Tools::displayError('Hook cannot be loaded.');
					else
					{
						if (!$module->unregisterHook((int)$id_hook) || !$module->unregisterExceptions((int)$id_hook))
							$this->errors[] = Tools::displayError('An error occurred while deleting module from hook.');
					}
				}
				if (!count($this->errors))
					Tools::redirectAdmin(self::$currentIndex.'&conf=17'.($this->display_key ? '&show_modules='.$this->display_key : '').'&token='.$this->token);
			}
		}
	}

	public function initContent()
	{
		if (array_key_exists('addToHook', $_GET) || array_key_exists('editGraft', $_GET) || (Tools::isSubmit('submitAddToHook') && $this->errors))
		{
			$this->display = 'edit';
			$this->content .= $this->renderForm();
		}
		else
			$this->content .= $this->initMain();

		$this->context->smarty->assign(array(
			'content' => $this->content
		));
	}

	public function initMain()
	{
		// Init toolbar
		$this->initToolbarTitle();

		$admin_dir = basename(_PS_ADMIN_DIR_);
		$modules = Module::getModulesInstalled();

		$assoc_modules_id = array();
		foreach ($modules as $module)
			if ($tmp_instance = Module::getInstanceById((int)$module['id_module']))
			{
				// We want to be able to sort modules by display name
				$module_instances[$tmp_instance->displayName] = $tmp_instance;
				// But we also want to associate hooks to modules using the modules IDs
				$assoc_modules_id[(int)$module['id_module']] = $tmp_instance->displayName;
			}
		ksort($module_instances);
		$hooks = Hook::getHooks(!(int)Tools::getValue('hook_position'));
		foreach ($hooks as $key => $hook)
		{
			// Get all modules for this hook or only the filtered module
			$hooks[$key]['modules'] = Hook::getModulesFromHook($hook['id_hook'], $this->display_key);
			$hooks[$key]['module_count'] = count($hooks[$key]['modules']);
			// If modules were found, link to the previously created Module instances
			if (is_array($hooks[$key]['modules']) && !empty($hooks[$key]['modules']))
				foreach ($hooks[$key]['modules'] as $module_key => $module)
					if (isset($assoc_modules_id[$module['id_module']]))
						$hooks[$key]['modules'][$module_key]['instance'] = $module_instances[$assoc_modules_id[$module['id_module']]];
		}

		$this->addJqueryPlugin('tablednd');

		$this->toolbar_btn['save'] = array(
			'href' => self::$currentIndex.'&addToHook'.($this->display_key ? '&show_modules='.$this->display_key : '').'&token='.$this->token,
			'desc' => $this->l('Transplant a module')
		);

		$this->context->smarty->assign(array(
			'show_toolbar' => true,
			'toolbar_btn' => $this->toolbar_btn,
			'title' => $this->toolbar_title,
			'toolbar_scroll' => 'false',
			'token' => $this->token,
			'url_show_modules' => self::$currentIndex.'&token='.$this->token.'&show_modules=',
			'modules' => $module_instances,
			'url_show_invisible' => self::$currentIndex.'&token='.$this->token.'&show_modules='.(int)Tools::getValue('show_modules').'&hook_position=',
			'hook_position' => Tools::getValue('hook_position'),
			'live_edit' => Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP,
			'url_live_edit' => $this->context->link->getPageLink('index', false, null,
				'live_edit&ad='.$admin_dir.'&liveToken='.sha1($admin_dir._COOKIE_KEY_)
				.(Shop::isFeatureActive()?'&id_shop='.Context::getContext()->shop->id : '')),
			'display_key' => $this->display_key,
			'hooks' => $hooks,
			'url_submit' => self::$currentIndex.'&token='.$this->token,
			'can_move' => (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) ? false : true,
		));

		return $this->createTemplate('list_modules.tpl')->fetch();
	}

	public function renderForm()
	{
		// Init toolbar
		$this->initToolbarTitle();
		// toolbar (save, cancel, new, ..)
		$this->initToolbar();
		$id_module = (int)Tools::getValue('id_module');
		$id_hook = (int)Tools::getValue('id_hook');
		if (Tools::isSubmit('editGraft'))
		{
			// Check auth for this page
			if (!$id_module || !$id_hook)
				Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);

			$sql = 'SELECT id_module
					FROM '._DB_PREFIX_.'hook_module
					WHERE id_module = '.$id_module.'
						AND id_hook = '.$id_hook.'
						AND id_shop IN('.implode(', ', Shop::getContextListShopID()).')';
			if (!Db::getInstance()->getValue($sql))
				Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);

			$sl_module = Module::getInstanceById($id_module);
			$excepts_list = $sl_module->getExceptions($id_hook, true);
			$excepts_diff = false;
			$excepts = '';
			if ($excepts_list)
			{
				$first = current($excepts_list);
				foreach ($excepts_list as $k => $v)
					if (array_diff($v, $first) || array_diff($first, $v))
						$excepts_diff = true;

				if (!$excepts_diff)
					$excepts = implode(', ', $first);
			}
		}
		else
		{
			$excepts_diff = false;
			$excepts_list = Tools::getValue('exceptions', array(array()));
		}
		$modules = Module::getModulesInstalled(0);

		$instances = array();
		foreach ($modules as $module)
			if ($tmp_instance = Module::getInstanceById($module['id_module']))
				$instances[$tmp_instance->displayName] = $tmp_instance;
		ksort($instances);
		$modules = $instances;
		$hooks = Hook::getHooks(0);

		$exception_list_diff = array();
		foreach ($excepts_list as $shop_id => $file_list)
			$exception_list_diff[] = $this->displayModuleExceptionList($file_list, $shop_id);

		$tpl = $this->createTemplate('form.tpl');
		$tpl->assign(array(
			'url_submit' => self::$currentIndex.'&token='.$this->token,
			'edit_graft' => Tools::isSubmit('editGraft'),
			'id_module' => (int)Tools::getValue('id_module'),
			'id_hook' => (int)Tools::getValue('id_hook'),
			'show_modules' => Tools::getValue('show_modules'),
			'hooks' => $hooks,
			'exception_list' => $this->displayModuleExceptionList(array_shift($excepts_list), 0),
			'exception_list_diff' => $exception_list_diff,
			'except_diff' => isset($excepts_diff) ? $excepts_diff : null,
			'display_key' => $this->display_key,
			'modules' => $modules,
			'show_toolbar' => true,
			'toolbar_btn' => $this->toolbar_btn,
			'title' => $this->toolbar_title,
			'table' => 'hook_module',
		));

		return $tpl->fetch();
	}

	public function displayModuleExceptionList($file_list, $shop_id)
	{
		if (!is_array($file_list))
			$file_list = ($file_list) ? array($file_list) : array();

		$content = '<input type="text" name="exceptions['.$shop_id.']" size="40" value="'.implode(', ', $file_list).'" id="em_text_'.$shop_id.'">';
		if ($shop_id)
		{
			$shop = new Shop($shop_id);
			$content .= ' ('.$shop->name.')';
		}
		$content .= '<br /><select id="em_list_'.$shop_id.'">';

		// @todo do something better with controllers
		$controllers = Dispatcher::getControllers(_PS_FRONT_CONTROLLER_DIR_);
		ksort($controllers);
		foreach ($controllers as $k => $v)
			$content .= '<option value="'.$k.'">'.$k.'</option>';
		$content .= '</select> <input type="button" class="button" value="'.$this->l('Add').'" onclick="position_exception_add('.$shop_id.')" />
				<input type="button" class="button" value="'.$this->l('Remove').'" onclick="position_exception_remove('.$shop_id.')" /><br /><br />';

		return $content;
	}
}
