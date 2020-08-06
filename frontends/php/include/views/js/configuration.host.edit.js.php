<?php
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


?>
<script type="text/x-jquery-tmpl" id="hostInterfaceRow">
<tr class="interfaceRow" id="hostInterfaceRow_#{iface.interfaceid}" data-interfaceid="#{iface.interfaceid}">
	<td class="interface-drag-control <?= ZBX_STYLE_TD_DRAG_ICON ?>">
		<div class="<?= ZBX_STYLE_DRAG_ICON ?>"></div>
		<input type="hidden" name="interfaces[#{iface.interfaceid}][items]" value="#{iface.items}" />
		<input type="hidden" name="interfaces[#{iface.interfaceid}][locked]" value="#{iface.locked}" />
	</td>
	<td class="interface-ip">
		<input type="hidden" name="interfaces[#{iface.interfaceid}][isNew]" value="#{iface.isNew}">
		<input type="hidden" name="interfaces[#{iface.interfaceid}][interfaceid]" value="#{iface.interfaceid}">
		<input type="hidden" id="interface_type_#{iface.interfaceid}" name="interfaces[#{iface.interfaceid}][type]" value="#{iface.type}">
		<input name="interfaces[#{iface.interfaceid}][ip]" type="text" style="width: <?= ZBX_TEXTAREA_INTERFACE_IP_WIDTH ?>px" maxlength="64" value="#{iface.ip}">
		<ul class="interface-bulk <?= ZBX_STYLE_LIST_CHECK_RADIO ?> <?= ZBX_STYLE_HOR_LIST ?>">
			<li>
				<input class="<?= ZBX_STYLE_CHECKBOX_RADIO ?>" type="checkbox" id="interfaces_#{iface.interfaceid}_bulk" name="interfaces[#{iface.interfaceid}][bulk]" value="1" #{attrs.checked_bulk}>
				<label for="interfaces_#{iface.interfaceid}_bulk"><span></span><?= _('Use bulk requests') ?></label>
			</li>
		</ul>
	</td>
	<td class="interface-dns">
		<?= (new CTextBox('interfaces[#{iface.interfaceid}][dns]', '#{iface.dns}', false,
				DB::getFieldLength('interface', 'dns'))
			)
				->setWidth(ZBX_TEXTAREA_INTERFACE_DNS_WIDTH)
		?>
	</td>
	<?= (new CCol(
			(new CRadioButtonList('interfaces[#{iface.interfaceid}][useip]', null))
				->addValue(_('IP'), INTERFACE_USE_IP, 'interfaces[#{iface.interfaceid}][useip]['.INTERFACE_USE_IP.']')
				->addValue(_('DNS'), INTERFACE_USE_DNS,
					'interfaces[#{iface.interfaceid}][useip]['.INTERFACE_USE_DNS.']'
				)
				->setModern(true)
		))->toString()
	?>
	<td class="interface-port">
		<?= (new CTextBox('interfaces[#{iface.interfaceid}][port]', '#{iface.port}', false, 64))
				->setWidth(ZBX_TEXTAREA_INTERFACE_PORT_WIDTH)
				->setAriaRequired()
		?>
	</td>
	<td class="interface-default">
		<input class="mainInterface <?= ZBX_STYLE_CHECKBOX_RADIO ?>" type="radio" id="interface_main_#{iface.interfaceid}" name="mainInterfaces[#{iface.type}]" value="#{iface.interfaceid}">
		<label class="checkboxLikeLabel" for="interface_main_#{iface.interfaceid}" style="height: 16px; width: 16px;"><span></span></label>
	</td>
	<td class="<?= ZBX_STYLE_NOWRAP ?> interface-control">
		<button class="<?= ZBX_STYLE_BTN_LINK ?> remove" type="button" id="removeInterface_#{iface.interfaceid}" data-interfaceid="#{iface.interfaceid}" #{attrs.disabled}><?= _('Remove') ?></button>
	</td>
</tr>
</script>

<script type="text/javascript">
	var hostInterfacesManager = (function() {
		'use strict';

		var rowTemplate = new Template(jQuery('#hostInterfaceRow').html()),
			ports = {
				agent: 10050,
				snmp: 161,
				jmx: 12345,
				ipmi: 623
			},
			allHostInterfaces = {};

		function renderHostInterfaceRow(hostInterface) {
			var domAttrs = getDomElementsAttrsForInterface(hostInterface),
				domId = getDomIdForRowInsert(hostInterface.type),
				domRow;

			jQuery(domId).before(rowTemplate.evaluate({iface: hostInterface, attrs: domAttrs}));

			domRow = jQuery('#hostInterfaceRow_' + hostInterface.interfaceid);

			if (hostInterface.type != <?= INTERFACE_TYPE_SNMP ?>) {
				jQuery('.interface-bulk', domRow).remove();
			}

			jQuery('#interfaces_' + hostInterface.interfaceid + '_useip_' + hostInterface.useip).prop('checked', true)
				.trigger('click');

			if (hostInterface.locked > 0) {
				addNotDraggableIcon(domRow);
			}
			else {
				addDraggableIcon(domRow);
			}
		}

		function resetMainInterfaces() {
			var typeInterfaces,
				hostInterfaces = getMainInterfacesByType();

			for (var hostInterfaceType in hostInterfaces) {
				typeInterfaces = hostInterfaces[hostInterfaceType];

				if (!typeInterfaces.main && typeInterfaces.all.length) {
					for (var i = 0; i < typeInterfaces.all.length; i++) {
						if (allHostInterfaces[typeInterfaces.all[i]].main === '1') {
							typeInterfaces.main = allHostInterfaces[typeInterfaces.all[i]].interfaceid;
						}
					}
					if (!typeInterfaces.main) {
						typeInterfaces.main = typeInterfaces.all[0];
						allHostInterfaces[typeInterfaces.main].main = '1';
					}
				}
			}

			for (var hostInterfaceType in hostInterfaces){
				typeInterfaces = hostInterfaces[hostInterfaceType];

				if (typeInterfaces.main) {
					jQuery('#interface_main_' + typeInterfaces.main).prop('checked', true);
				}
			}
		}

		function getMainInterfacesByType() {
			var hostInterface,
				types = {};
			types[getHostInterfaceNumericType('agent')] = {main: null, all: []};
			types[getHostInterfaceNumericType('snmp')] = {main: null, all: []};
			types[getHostInterfaceNumericType('jmx')] = {main: null, all: []};
			types[getHostInterfaceNumericType('ipmi')] = {main: null, all: []};

			for (var hostInterfaceId in allHostInterfaces) {
				hostInterface = allHostInterfaces[hostInterfaceId];

				types[hostInterface.type].all.push(hostInterfaceId);
				if (hostInterface.main === '1') {
					if (types[hostInterface.type].main !== null) {
						throw new Error('Multiple default interfaces for same type.');
					}
					types[hostInterface.type].main = hostInterfaceId;
				}
			}
			return types;
		}

		function addDraggableIcon(domElement) {
			domElement.draggable({
				handle: 'div.<?= ZBX_STYLE_DRAG_ICON ?>',
				opacity: 0.6,
				revert: 'invalid',
				helper: function(event) {
					var hostInterfaceId = jQuery(this).data('interfaceid');
					var clone = jQuery(this).clone();
					// Make sure to update names for all radio and checkboxes for them not to affect selection of
					// originals.
					// If original is addressed by any means (ex. by ID), make sure, to update these means in clone,
					// for clone not to be addressed in place of original.
					clone.find("[name$='[useip]']").each(function(){
						jQuery(this).attr('name','interfaces[' + hostInterfaceId + '][useip_handle]');
					});
					clone.find("#interface_main_" + hostInterfaceId)
						.attr('name','mainInterfaces[handle]')
						.attr('id','interface_main_' + hostInterfaceId + '_handle');
					return clone;
				},
				start: function(event, ui) {
					jQuery(ui.helper).css({'z-index': '1000'});
					// Visibility is added to original element to hide it, while helper is being moved, but to keep
					// it's place visually.
					jQuery(this).css({'visibility': 'hidden'});
				},
				stop: function(event, ui) {
					resetMainInterfaces();
					jQuery(this).css({'visibility': ''});
				}
			});
		}

		function addNotDraggableIcon(domElement) {
			jQuery('td.<?= ZBX_STYLE_TD_DRAG_ICON ?> div.<?= ZBX_STYLE_DRAG_ICON ?>', domElement)
				.addClass('<?= ZBX_STYLE_DISABLED ?>')
				.hover(
					function (event) {
						hintBox.showHint(event, this,
							<?= CJs::encodeJson(_('Interface is used by items that require this type of the interface.')) ?>
						);
					},
					function () {
						hintBox.hideHint(this);
					}
				);
		}

		function getDomElementsAttrsForInterface(hostInterface) {
			var attrs = {
				disabled: ''
			};

			if (hostInterface.items > 0) {
				attrs.disabled = 'disabled="disabled"';
			}

			if (hostInterface.type == <?= INTERFACE_TYPE_SNMP ?>) {
				if (hostInterface.bulk == 1) {
					attrs.checked_bulk = 'checked=checked';
				}
				else {
					attrs.checked_bulk = '';
				}
			}

			return attrs;
		}

		function getDomIdForRowInsert(hostInterfaceType) {
			var footerRowId;

			switch (hostInterfaceType) {
				case getHostInterfaceNumericType('agent'):
					footerRowId = '#agentInterfacesFooter';
					break;
				case getHostInterfaceNumericType('snmp'):
					footerRowId = '#SNMPInterfacesFooter';
					break;
				case getHostInterfaceNumericType('jmx'):
					footerRowId = '#JMXInterfacesFooter';
					break;
				case getHostInterfaceNumericType('ipmi'):
					footerRowId = '#IPMIInterfacesFooter';
					break;
				default:
					throw new Error('Unknown host interface type.');
			}
			return footerRowId;
		}

		function createNewHostInterface(hostInterfaceType) {
			var newInterface = {
				isNew: true,
				useip: 1,
				type: getHostInterfaceNumericType(hostInterfaceType),
				port: ports[hostInterfaceType],
				ip: '127.0.0.1'
			};

			if (newInterface.type == <?= INTERFACE_TYPE_SNMP ?>) {
				newInterface.bulk = 1;
			}

			newInterface.interfaceid = 1;
			while (allHostInterfaces[newInterface.interfaceid] !== void(0)) {
				newInterface.interfaceid++;
			}

			addHostInterface(newInterface);

			return newInterface;
		}

		function addHostInterface(hostInterface) {
			allHostInterfaces[hostInterface.interfaceid] = hostInterface;
		}

		function moveRowToAnotherTypeTable(hostInterfaceId, newHostInterfaceType) {
			var newDomId = getDomIdForRowInsert(newHostInterfaceType);

			jQuery('#interface_main_' + hostInterfaceId).attr('name', 'mainInterfaces[' + newHostInterfaceType + ']');
			jQuery('#interface_main_' + hostInterfaceId).prop('checked', false);
			jQuery('#interface_type_' + hostInterfaceId).val(newHostInterfaceType);
			jQuery('#hostInterfaceRow_' + hostInterfaceId).insertBefore(newDomId);
		}

		return {
			add: function(hostInterfaces) {
				for (var i = 0; i < hostInterfaces.length; i++) {
					addHostInterface(hostInterfaces[i]);
					renderHostInterfaceRow(hostInterfaces[i]);
				}
				resetMainInterfaces();
			},

			addNew: function(type) {
				var hostInterface = createNewHostInterface(type);

				allHostInterfaces[hostInterface.interfaceid] = hostInterface;
				renderHostInterfaceRow(hostInterface);
				resetMainInterfaces();
			},

			remove: function(hostInterfaceId) {
				delete allHostInterfaces[hostInterfaceId];
			},

			setType: function(hostInterfaceId, typeName) {
				var newTypeNum = getHostInterfaceNumericType(typeName);

				if (allHostInterfaces[hostInterfaceId].type !== newTypeNum) {
					moveRowToAnotherTypeTable(hostInterfaceId, newTypeNum);
					allHostInterfaces[hostInterfaceId].type = newTypeNum;
					allHostInterfaces[hostInterfaceId].main = '0';
				}
			},

			resetMainInterfaces: function() {
				resetMainInterfaces();
			},

			setMainInterface: function(hostInterfaceId) {
				var interfacesByType = getMainInterfacesByType(),
					newMainInterfaceType = allHostInterfaces[hostInterfaceId].type,
					oldMainInterfaceId = interfacesByType[newMainInterfaceType].main;

				if (hostInterfaceId !== oldMainInterfaceId) {
					allHostInterfaces[hostInterfaceId].main = '1';
					allHostInterfaces[oldMainInterfaceId].main = '0';
				}
			},

			setUseipForInterface: function(hostInterfaceId, useip) {
				allHostInterfaces[hostInterfaceId].useip = useip;
			},

			disable: function() {
				jQuery('.interface-drag-control, .interface-control').html('');
				jQuery('.interfaceRow').find('input')
					.removeAttr('id')
					.removeAttr('name');
				jQuery('.interfaceRow').find('input[type="text"]').prop('readonly', true);
				jQuery('.interfaceRow').find('input[type="radio"], input[type="checkbox"]').prop('disabled', true);
			}
		}
	}());

	jQuery(document).ready(function() {
		'use strict';

		jQuery('#hostlist').on('click', 'button.remove', function() {
			var interfaceId = jQuery(this).data('interfaceid');
			jQuery('#hostInterfaceRow_' + interfaceId).remove();
			hostInterfacesManager.remove(interfaceId);
			hostInterfacesManager.resetMainInterfaces();
		});

		jQuery('#hostlist').on('click', 'input[type=radio].mainInterface', function() {
			var interfaceId = jQuery(this).val();
			hostInterfacesManager.setMainInterface(interfaceId);
		});

		jQuery('#hostlist').on('click', 'input[type=radio][id*="_useip_"]', function() {
			var interfaceId = jQuery(this).attr('id').match(/\d+/);
			hostInterfacesManager.setUseipForInterface(interfaceId[0], jQuery(this).val());

			jQuery('[name^="interfaces['+interfaceId[0]+']["]')
				.filter('[name$="[ip]"],[name$="[dns]"]')
				.removeAttr('aria-required')
				.filter((jQuery(this).val() == <?= INTERFACE_USE_IP ?>) ? '[name$="[ip]"]' : '[name$="[dns]"]')
				.attr('aria-required', true);
		});

		jQuery('#tls_connect, #tls_in_psk, #tls_in_cert').change(function() {
			// If certificate is selected or checked.
			if (jQuery('input[name=tls_connect]:checked').val() == <?= HOST_ENCRYPTION_CERTIFICATE ?>
					|| jQuery('#tls_in_cert').is(':checked')) {
				jQuery('#tls_issuer, #tls_subject').closest('li').show();
			}
			else {
				jQuery('#tls_issuer, #tls_subject').closest('li').hide();
			}

			// If PSK is selected or checked.
			if (jQuery('input[name=tls_connect]:checked').val() == <?= HOST_ENCRYPTION_PSK ?>
					|| jQuery('#tls_in_psk').is(':checked')) {
				jQuery('#tls_psk, #tls_psk_identity').closest('li').show();
			}
			else {
				jQuery('#tls_psk, #tls_psk_identity').closest('li').hide();
			}
		});

		jQuery('#agentInterfaces, #SNMPInterfaces, #JMXInterfaces, #IPMIInterfaces').parent().droppable({
			tolerance: 'pointer',
			drop: function(event, ui) {
				var hostInterfaceTypeName = jQuery(this).data('type'),
					hostInterfaceId = ui.draggable.data('interfaceid');

				if (getHostInterfaceNumericType(hostInterfaceTypeName) == <?= INTERFACE_TYPE_SNMP ?>) {
					if (jQuery('.interface-bulk', jQuery('#hostInterfaceRow_' + hostInterfaceId)).length == 0) {
						var bulkList = jQuery('<ul>', {
							'class': 'interface-bulk <?= ZBX_STYLE_LIST_CHECK_RADIO ?> <?= ZBX_STYLE_HOR_LIST ?>'
						});

						var bulkItem = jQuery('<li>');

						// append checkbox
						bulkItem.append(jQuery('<input>', {
							id: 'interfaces_' + hostInterfaceId + '_bulk',
							type: 'checkbox',
							class: '<?= ZBX_STYLE_CHECKBOX_RADIO ?>',
							name: 'interfaces[' + hostInterfaceId + '][bulk]',
							value: 1,
							checked: true
						}));

						// append label
						var bulkLabel = jQuery('<label>', {
							'for': 'interfaces_' + hostInterfaceId + '_bulk',
						});

						bulkLabel.append(jQuery('<span>'));
						bulkLabel.append(<?= CJs::encodeJson(_('Use bulk requests')) ?>);

						bulkItem.append(bulkLabel);
						bulkList.append(bulkItem);

						jQuery('.interface-ip', jQuery('#hostInterfaceRow_' + hostInterfaceId)).append(bulkList);
					}
				}
				else {
					jQuery('.interface-bulk', jQuery('#hostInterfaceRow_' + hostInterfaceId)).remove();
				}

				hostInterfacesManager.setType(hostInterfaceId, hostInterfaceTypeName);
			},
			activate: function(event, ui) {
				if (!jQuery(this).find(ui.draggable).length) {
					jQuery(this).addClass('<?= ZBX_STYLE_DRAG_DROP_AREA ?>');
				}
			},
			deactivate: function(event, ui) {
				jQuery(this).removeClass('<?= ZBX_STYLE_DRAG_DROP_AREA ?>');
			}
		});

		jQuery('#addAgentInterface').on('click', function() {
			hostInterfacesManager.addNew('agent');
		});
		jQuery('#addSNMPInterface').on('click', function() {
			hostInterfacesManager.addNew('snmp');
		});
		jQuery('#addJMXInterface').on('click', function() {
			hostInterfacesManager.addNew('jmx');
		});
		jQuery('#addIPMIInterface').on('click', function() {
			hostInterfacesManager.addNew('ipmi');
		});

		// radio button of inventory modes was clicked
		jQuery('input[name=inventory_mode]').click(function() {
			// action depending on which button was clicked
			var inventoryFields = jQuery('#inventorylist :input:gt(2)');

			switch (jQuery(this).val()) {
				case '<?= HOST_INVENTORY_DISABLED ?>':
					inventoryFields.prop('disabled', true);
					jQuery('.populating_item').hide();
					break;
				case '<?= HOST_INVENTORY_MANUAL ?>':
					inventoryFields.prop('disabled', false);
					jQuery('.populating_item').hide();
					break;
				case '<?= HOST_INVENTORY_AUTOMATIC ?>':
					inventoryFields.prop('disabled', false);
					inventoryFields.filter('.linked_to_item').prop('disabled', true);
					jQuery('.populating_item').show();
					break;
			}
		});

		/**
		 * Mass update
		 */
		jQuery('#mass_replace_tpls').on('change', function() {
			jQuery('#mass_clear_tpls').prop('disabled', !this.checked);
		}).change();

		// Refresh field visibility on document load.
		if ((jQuery('#tls_accept').val() & <?= HOST_ENCRYPTION_NONE ?>) == <?= HOST_ENCRYPTION_NONE ?>) {
			jQuery('#tls_in_none').prop('checked', true);
		}
		if ((jQuery('#tls_accept').val() & <?= HOST_ENCRYPTION_PSK ?>) == <?= HOST_ENCRYPTION_PSK ?>) {
			jQuery('#tls_in_psk').prop('checked', true);
		}
		if ((jQuery('#tls_accept').val() & <?= HOST_ENCRYPTION_CERTIFICATE ?>) == <?= HOST_ENCRYPTION_CERTIFICATE ?>) {
			jQuery('#tls_in_cert').prop('checked', true);
		}

		jQuery('input[name=tls_connect]').trigger('change');

		// Depending on checkboxes, create a value for hidden field 'tls_accept'.
		jQuery('#hostsForm').submit(function() {
			var tls_accept = 0x00;

			if (jQuery('#tls_in_none').is(':checked')) {
				tls_accept |= <?= HOST_ENCRYPTION_NONE ?>;
			}
			if (jQuery('#tls_in_psk').is(':checked')) {
				tls_accept |= <?= HOST_ENCRYPTION_PSK ?>;
			}
			if (jQuery('#tls_in_cert').is(':checked')) {
				tls_accept |= <?= HOST_ENCRYPTION_CERTIFICATE ?>;
			}

			jQuery('#tls_accept').val(tls_accept);
		});
	});

	function getHostInterfaceNumericType(typeName) {
		var typeNum;

		switch (typeName) {
			case 'agent':
				typeNum = '<?= INTERFACE_TYPE_AGENT ?>';
				break;
			case 'snmp':
				typeNum = '<?= INTERFACE_TYPE_SNMP ?>';
				break;
			case 'jmx':
				typeNum = '<?= INTERFACE_TYPE_JMX ?>';
				break;
			case 'ipmi':
				typeNum = '<?= INTERFACE_TYPE_IPMI ?>';
				break;
			default:
				throw new Error('Unknown host interface type name.');
		}
		return typeNum;
	}
</script>
