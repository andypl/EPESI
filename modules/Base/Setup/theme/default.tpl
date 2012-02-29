{php}
	load_js($this->get_template_vars('theme_dir').'/Base/Setup/default.js');
{/php}


<div id="Base_Setup">
	{foreach key=name item=package from=$packages}
		<div class="big-button" style="position:relative;">
			<div class="package_label">
				{$name}
			</div>
			<div class="actions">
				<div id="show_actions_{$name}" class="action {$package.style}" onclick="base_setup__show_actions('{$name}');">
					{$package.status}{if !empty($package.buttons)}<img src="{$theme_dir}/Base/Setup/config.png">{/if}
				</div>
			{if !empty($package.buttons)}
				<div class="action" id="hide_actions_{$name}" style="position: absolute; top:0px; left:12px; z-index: 5; display:none;">
					<div class="subaction {$package.style}" onclick="base_setup__hide_actions('{$name}');">
						{$package.status}<img src="{$theme_dir}/Base/Setup/config-up.png">
					</div>
					{foreach from=$package.buttons item=button}
						<div {$button.href} class="subaction {$button.style}">
							{$button.label}
						</div>
					{/foreach}
				</div>
			{/if}
			{if !empty($package.options)}
				<div id="show_options_{$name}" class="action toggle_options" onclick="base_setup__show_options('{$name}');">
					Optional<img src="{$theme_dir}/Base/Setup/config.png">
				</div>
				<div id="hide_options_{$name}" class="action toggle_options" onclick="base_setup__hide_options('{$name}');" style="display:none;">
					Optional<img src="{$theme_dir}/Base/Setup/config-up.png">
				</div>
			{/if}
			</div>
			<div class="package_icon" style="display:none;">
				<img src="{$package.icon}" border="1">
			</div>
			{if !empty($package.options)}
				<div class="options" id="options_{$name}" style="display:none;">
					<div class="options_cover" style="width:100%; height: 5px;"></div>
					{foreach from=$package.options key=option item=action}
						<div class="option_spacer"></div>
						<div class="option">
							<div class="option_action">
								<div id="show_actions_button_{$name}__{$option}" class="action {$action.style}" onclick="base_setup__show_actions('{$name}','{$option}');">
									{$action.status}{if !empty($action.buttons)}<img src="{$theme_dir}/Base/Setup/config.png">{/if}
								</div>
								{if !empty($action.buttons)}
									<div id="hide_actions_button_{$name}__{$option}" class="action {$action.style}" onclick="base_setup__hide_actions('{$name}','{$option}');" style="display: none;">
										{$action.status}<img src="{$theme_dir}/Base/Setup/config-up.png">
									</div>
								{/if}
							</div>
							<div class="option_label">
								{$option}
							</div>
						</div>
						{if !empty($action.buttons)}
							<div class="actions" id="hide_actions_{$name}__{$option}" style="display:none;">
								{foreach from=$action.buttons item=button}
									<div {$button.href} class="action {$button.style}" onclick="Effect.Fade($('hide_actions_{$name}'), {literal}{duration:0.4}{/literal});">
										{$button.label}
									</div>
								{/foreach}
							</div>
						{/if}
					{/foreach}
				</div>
			{/if}
		</div>
	{/foreach}
</div>