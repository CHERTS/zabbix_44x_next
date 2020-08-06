<script type="text/x-jquery-tmpl" id="dcheckRowTPL">
	<?= (new CRow([
			(new CCol(
				(new CDiv('#{name}'))->addClass(ZBX_STYLE_WORDWRAP)
			))->setId('dcheckCell_#{dcheckid}'),
			(new CHorList([
				(new CButton(null, _('Edit')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->onClick("javascript: showNewCheckForm(null, '#{dcheckid}');"),
				(new CButton(null, _('Remove')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->onClick("javascript: removeDCheckRow('#{dcheckid}');")
			]))->addClass(ZBX_STYLE_NOWRAP)
		]))
			->setId('dcheckRow_#{dcheckid}')
			->toString()
	?>
</script>
<script type="text/x-jquery-tmpl" id="uniqRowTPL">
	<?=	(new CListItem([
			(new CInput('radio', 'uniqueness_criteria', '#{dcheckid}'))
				->addClass(ZBX_STYLE_CHECKBOX_RADIO)
				->setId('uniqueness_criteria_#{dcheckid}'),
			(new CLabel([new CSpan(), '#{name}'], 'uniqueness_criteria_#{dcheckid}'))->addClass(ZBX_STYLE_WORDWRAP)
		]))
			->setId('uniqueness_criteria_row_#{dcheckid}')
			->toString()
	?>
</script>
<script type="text/x-jquery-tmpl" id="hostSourceRowTPL">
	<?=	(new CListItem([
			(new CInput('radio', 'host_source', '_#{dcheckid}'))
				->addClass(ZBX_STYLE_CHECKBOX_RADIO)
				->setAttribute('data-id', '#{dcheckid}')
				->setId('host_source_#{dcheckid}'),
			(new CLabel([new CSpan(), '#{name}'], 'host_source_#{dcheckid}'))->addClass(ZBX_STYLE_WORDWRAP)
		]))
			->setId('host_source_row_#{dcheckid}')
			->toString()
	?>
</script>
<script type="text/x-jquery-tmpl" id="nameSourceRowTPL">
	<?=	(new CListItem([
			(new CInput('radio', 'name_source', '_#{dcheckid}'))
				->addClass(ZBX_STYLE_CHECKBOX_RADIO)
				->setAttribute('data-id', '#{dcheckid}')
				->setId('name_source_#{dcheckid}'),
			(new CLabel([new CSpan(), '#{name}'], 'name_source_#{dcheckid}'))->addClass(ZBX_STYLE_WORDWRAP)
		]))
			->setId('name_source_row_#{dcheckid}')
			->toString()
	?>
</script>
<script type="text/x-jquery-tmpl" id="newDCheckTPL">
<?=
	(new CDiv(
		(new CDiv([
			(new CFormList())
				->addRow(
					(new CLabel(_('Check type'), 'type')),
					(new CComboBox('type'))
				)
				->addRow(
					(new CLabel(_('Port range'), 'ports'))->setAsteriskMark(),
					(new CTextBox('ports'))
						->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
						->setAriaRequired(),
					'newCheckPortsRow'
				)
				->addRow(
					(new CLabel(_('SNMP community'), 'snmp_community'))->setAsteriskMark(),
					(new CTextBox('snmp_community'))
						->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH)
						->setAriaRequired(),
					'newCheckCommunityRow'
				)
				->addRow(
					(new CLabel(_('Key'), 'key_'))->setAsteriskMark(),
					(new CTextBox('key_'))
						->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH)
						->setAriaRequired(),
					'newCheckKeyRow'
				)
				->addRow(
					(new CLabel(_('SNMP OID'), 'snmp_oid'))->setAsteriskMark(),
					(new CTextBox('snmp_oid'))
						->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH)
						->setAriaRequired()
						->setAttribute('maxlength', 512),
					'new_check_snmp_oid_row'
				)
				->addRow(
					(new CLabel(_('Context name'), 'snmpv3_contextname')),
					(new CTextBox('snmpv3_contextname'))
						->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH),
					'newCheckContextRow'
				)
				->addRow(
					(new CLabel(_('Security name'), 'snmpv3_securityname')),
					(new CTextBox('snmpv3_securityname'))
						->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH)
						->setAttribute('maxlength', 64),
					'newCheckSecNameRow'
				)
				->addRow(
					new CLabel(_('Security level'), 'snmpv3_securitylevel'),
					new CComboBox('snmpv3_securitylevel', null, null, [
						ITEM_SNMPV3_SECURITYLEVEL_NOAUTHNOPRIV => 'noAuthNoPriv',
						ITEM_SNMPV3_SECURITYLEVEL_AUTHNOPRIV => 'authNoPriv',
						ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV => 'authPriv'
					]),
					'newCheckSecLevRow'
				)
				->addRow(
					(new CLabel(_('Authentication protocol'), 'snmpv3_authprotocol')),
					(new CRadioButtonList('snmpv3_authprotocol', ITEM_AUTHPROTOCOL_MD5))
						->addValue(_('MD5'), ITEM_AUTHPROTOCOL_MD5, 'snmpv3_authprotocol_'.ITEM_AUTHPROTOCOL_MD5)
						->addValue(_('SHA'), ITEM_AUTHPROTOCOL_SHA, 'snmpv3_authprotocol_'.ITEM_AUTHPROTOCOL_SHA)
						->setModern(true),
					'newCheckAuthProtocolRow'
				)
				->addRow(
					(new CLabel(_('Authentication passphrase'), 'snmpv3_authpassphrase')),
					(new CTextBox('snmpv3_authpassphrase'))
						->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH)
						->setAttribute('maxlength', 64),
					'newCheckAuthPassRow'
				)
				->addRow(
					(new CLabel(_('Privacy protocol'), 'snmpv3_privprotocol')),
					(new CRadioButtonList('snmpv3_privprotocol', ITEM_PRIVPROTOCOL_DES))
						->addValue(_('DES'), ITEM_PRIVPROTOCOL_DES, 'snmpv3_privprotocol_'.ITEM_PRIVPROTOCOL_DES)
						->addValue(_('AES'), ITEM_PRIVPROTOCOL_AES, 'snmpv3_privprotocol_'.ITEM_PRIVPROTOCOL_AES)
						->setModern(true),
					'newCheckPrivProtocolRow'
				)
				->addRow(
					(new CLabel(_('Privacy passphrase'), 'snmpv3_privpassphrase'))->setAsteriskMark(),
					(new CTextBox('snmpv3_privpassphrase'))
						->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH)
						->setAriaRequired()
						->setAttribute('maxlength', 64),
					'newCheckPrivPassRow'
				),
			(new CHorList([
				(new CButton('add_new_dcheck', _('Add')))->addClass(ZBX_STYLE_BTN_LINK),
				(new CButton('cancel_new_dcheck', _('Cancel')))->addClass(ZBX_STYLE_BTN_LINK)
			]))
		]))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->addStyle('width: '.ZBX_TEXTAREA_STANDARD_WIDTH.'px')
	))->setId('new_check_form')
?>
</script>
<script type="text/javascript">
	var ZBX_SVC = {
		ssh: <?= SVC_SSH ?>,
		ldap: <?= SVC_LDAP ?>,
		smtp: <?= SVC_SMTP ?>,
		ftp: <?= SVC_FTP ?>,
		http: <?= SVC_HTTP ?>,
		pop: <?= SVC_POP ?>,
		nntp: <?= SVC_NNTP ?>,
		imap: <?= SVC_IMAP ?>,
		tcp: <?= SVC_TCP ?>,
		agent: <?= SVC_AGENT ?>,
		snmpv1: <?= SVC_SNMPv1 ?>,
		snmpv2: <?= SVC_SNMPv2c ?>,
		snmpv3: <?= SVC_SNMPv3 ?>,
		icmp: <?= SVC_ICMPPING ?>,
		https: <?= SVC_HTTPS ?>,
		telnet: <?= SVC_TELNET ?>
	};

	var ZBX_CHECKLIST = {};

	function discoveryCheckDefaultPort(service) {
		var defPorts = {};
		defPorts[ZBX_SVC.ssh] = '22';
		defPorts[ZBX_SVC.ldap] = '389';
		defPorts[ZBX_SVC.smtp] = '25';
		defPorts[ZBX_SVC.ftp] = '21';
		defPorts[ZBX_SVC.http] = '80';
		defPorts[ZBX_SVC.pop] = '110';
		defPorts[ZBX_SVC.nntp] = '119';
		defPorts[ZBX_SVC.imap] = '143';
		defPorts[ZBX_SVC.tcp] = '0';
		defPorts[ZBX_SVC.icmp] = '0';
		defPorts[ZBX_SVC.agent] = '10050';
		defPorts[ZBX_SVC.snmpv1] = '161';
		defPorts[ZBX_SVC.snmpv2] = '161';
		defPorts[ZBX_SVC.snmpv3] = '161';
		defPorts[ZBX_SVC.https] = '443';
		defPorts[ZBX_SVC.telnet] = '23';

		service = service.toString();

		return isset(service, defPorts) ? defPorts[service] : 0;
	}

	function discoveryCheckTypeToString(svcPort) {
		var defPorts = {};
		defPorts[ZBX_SVC.ftp] = <?= CJs::encodeJson(_('FTP')) ?>;
		defPorts[ZBX_SVC.http] = <?= CJs::encodeJson(_('HTTP')) ?>;
		defPorts[ZBX_SVC.https] = <?= CJs::encodeJson(_('HTTPS')) ?>;
		defPorts[ZBX_SVC.icmp] = <?= CJs::encodeJson(_('ICMP ping')) ?>;
		defPorts[ZBX_SVC.imap] = <?= CJs::encodeJson(_('IMAP')) ?>;
		defPorts[ZBX_SVC.tcp] = <?= CJs::encodeJson(_('TCP')) ?>;
		defPorts[ZBX_SVC.ldap] = <?= CJs::encodeJson(_('LDAP')) ?>;
		defPorts[ZBX_SVC.nntp] = <?= CJs::encodeJson(_('NNTP')) ?>;
		defPorts[ZBX_SVC.pop] = <?= CJs::encodeJson(_('POP')) ?>;
		defPorts[ZBX_SVC.snmpv1] = <?= CJs::encodeJson(_('SNMPv1 agent')) ?>;
		defPorts[ZBX_SVC.snmpv2] = <?= CJs::encodeJson(_('SNMPv2 agent')) ?>;
		defPorts[ZBX_SVC.snmpv3] = <?= CJs::encodeJson(_('SNMPv3 agent')) ?>;
		defPorts[ZBX_SVC.smtp] = <?= CJs::encodeJson(_('SMTP')) ?>;
		defPorts[ZBX_SVC.ssh] = <?= CJs::encodeJson(_('SSH')) ?>;
		defPorts[ZBX_SVC.telnet] = <?= CJs::encodeJson(_('Telnet')) ?>;
		defPorts[ZBX_SVC.agent] = <?= CJs::encodeJson(_('Zabbix agent')) ?>;

		if (typeof svcPort === 'undefined') {
			return defPorts;
		}

		svcPort = parseInt(svcPort, 10);

		return isset(svcPort, defPorts) ? defPorts[svcPort] : <?= CJs::encodeJson(_('Unknown')) ?>;
	}

	/**
	 * Checks if type of SNMP.
	 *
	 * @param integer type
	 *
	 * @return bool
	 */
	function typeOfSnmp(type) {
		var types = {};
		types[ZBX_SVC.snmpv1] = true;
		types[ZBX_SVC.snmpv2] = true;
		types[ZBX_SVC.snmpv3] = true;

		return (typeof types[type] != 'undefined');
	}

	function toggleInputs(id, state) {
		jQuery('#' + id).toggle(state);

		if (state) {
			jQuery('#' + id + ' :input').prop('disabled', false);
		}
		else {
			jQuery('#' + id + ' :input').prop('disabled', true);
		}
	}

	/**
	 * @see init.js add.popup event
	 */
	function addPopupValues(list) {
		// templates
		var dcheckRowTpl = new Template(jQuery('#dcheckRowTPL').html()),
			uniqRowTpl = new Template(jQuery('#uniqRowTPL').html()),
			hostSourceRowTPL = new Template(jQuery('#hostSourceRowTPL').html()),
			nameSourceRowTPL = new Template(jQuery('#nameSourceRowTPL').html());

		for (var i = 0; i < list.length; i++) {
			if (empty(list[i])) {
				continue;
			}

			var value = list[i];

			if (typeof value.dcheckid === 'undefined') {
				value.dcheckid = getUniqueId();
			}

			// add
			if (typeof ZBX_CHECKLIST[value.dcheckid] === 'undefined') {
				ZBX_CHECKLIST[value.dcheckid] = value;

				jQuery('#dcheckListFooter').before(dcheckRowTpl.evaluate(value));

				for (var fieldName in value) {
					if (typeof value[fieldName] === 'string') {
						var input = jQuery('<input>', {
							name: 'dchecks[' + value.dcheckid + '][' + fieldName + ']',
							type: 'hidden',
							value: value[fieldName]
						});

						jQuery('#dcheckCell_' + value.dcheckid).append(input);
					}
				}
			}

			// update
			else {
				ZBX_CHECKLIST[value.dcheckid] = value;

				var ignoreNames = ['druleid', 'dcheckid', 'name', 'ports', 'type', 'uniq'];

				// clean values
				jQuery('#dcheckCell_' + value.dcheckid + ' input').each(function(i, item) {
					var itemObj = jQuery(item);

					var name = itemObj.attr('name').replace('dchecks[' + value.dcheckid + '][', '');
					name = name.substring(0, name.length - 1);

					if (jQuery.inArray(name, ignoreNames) == -1) {
						itemObj.remove();
					}
				});

				// set values
				for (var fieldName in value) {
					if (typeof value[fieldName] === 'string') {
						var obj = jQuery('input[name="dchecks[' + value.dcheckid + '][' + fieldName + ']"]');

						if (obj.length) {
							obj.val(value[fieldName]);
						}
						else {
							var input = jQuery('<input>', {
								name: 'dchecks[' + value.dcheckid + '][' + fieldName + ']',
								type: 'hidden',
								value: value[fieldName]
							});

							jQuery('#dcheckCell_' + value.dcheckid).append(input);
						}
					}
				}

				// update check name
				jQuery('#dcheckCell_' + value.dcheckid + ' .wordwrap').text(value['name']);
			}

			var availableDeviceTypes = [ZBX_SVC.agent, ZBX_SVC.snmpv1, ZBX_SVC.snmpv2, ZBX_SVC.snmpv3],
				elements = {
					uniqueness_criteria: ['ip', uniqRowTpl.evaluate(value)],
					host_source: ['chk_dns', hostSourceRowTPL.evaluate(value)],
					name_source: ['chk_host', nameSourceRowTPL.evaluate(value)]
				};

			jQuery.each(elements, function(key, param) {
				var	obj = jQuery('#' + key + '_row_' + value.dcheckid);

				if (jQuery.inArray(parseInt(value.type, 10), availableDeviceTypes) > -1) {
					var new_obj = param[1];
					if (obj.length) {
						var checked_id = jQuery('input:radio[name=' + key + ']:checked').attr('id');
						obj.replaceWith(new_obj);
						jQuery('#' + checked_id).prop('checked', true);
					}
					else {
						jQuery('#' + key).append(new_obj);
					}
				}
				else {
					if (obj.length) {
						obj.remove();
						jQuery('#' + key + '_' + param[0]).prop('checked', true);
					}
				}
			});
		}
	}

	function removeDCheckRow(dcheckid) {
		jQuery('#dcheckRow_' + dcheckid).remove();

		delete(ZBX_CHECKLIST[dcheckid]);

		var elements = {
			uniqueness_criteria_: 'ip',
			host_source_: 'chk_dns',
			name_source_: 'chk_host'
		};

		jQuery.each(elements, function(key, def) {
			var obj = jQuery('#' + key + dcheckid);

			if (obj.length) {
				if (obj.is(':checked')) {
					jQuery('#' + key + def).prop('checked', true);
				}
				jQuery('#' + key + 'row_' + dcheckid).remove();
			}
		});

	}

	function showNewCheckForm(e, dcheckId) {
		var isUpdate = (typeof dcheckId !== 'undefined');

		// remove existing form
		jQuery('#new_check_form').remove();

		if (jQuery('#new_check_form').length == 0) {
			var tpl = new Template(jQuery('#newDCheckTPL').html());

			jQuery('#dcheckList').after(tpl.evaluate());

			// display fields dependent from type
			jQuery('#type').change(function() {
				updateNewDCheckType(dcheckId);
			});

			// display addition snmpv3 security level fields dependent from snmpv3 security level
			jQuery('#snmpv3_securitylevel').change(updateNewDCheckSNMPType);

			// button "add"
			jQuery('#add_new_dcheck').click(function() {
				saveNewDCheckForm(dcheckId, this);
			});

			// rename button to "update"
			if (isUpdate) {
				jQuery('#add_new_dcheck').text(<?= CJs::encodeJson(_('Update')) ?>);
			}

			// button "remove" form
			jQuery('#cancel_new_dcheck').click(function() {
				jQuery('#new_check_form').remove();
			});

			// port name sorting
			var svcPorts = discoveryCheckTypeToString(),
				portNameSvcValue = {},
				portNameOrder = [];

			for (var key in svcPorts) {
				portNameOrder.push(svcPorts[key]);
				portNameSvcValue[svcPorts[key]] = key;
			}

			portNameOrder.sort();

			for (var i = 0; i < portNameOrder.length; i++) {
				var portName = portNameOrder[i];

				jQuery('#type').append(jQuery('<option>', {
					value: portNameSvcValue[portName],
					text: portName
				}));
			}
		}

		// restore form values
		if (isUpdate) {
			var dcheck_inputs = jQuery('#dcheckCell_' + dcheckId + ' input'),
				check_type = dcheck_inputs.filter('[name="dchecks[' + dcheckId + '][type]"]').val();

			dcheck_inputs.each(function(i, item) {
				var itemObj = jQuery(item);

				var name = itemObj.attr('name').replace('dchecks[' + dcheckId + '][', '');
				name = name.substring(0, name.length - 1);

				// ignore "name" value because it is virtual
				if (name !== 'name') {
					if (name == 'key_' && typeOfSnmp(check_type)) {
						// Use key_ value in snmp_oid input.

						jQuery('#snmp_oid').val(itemObj.val());
					}
					else if (name === 'host_source' || name === 'name_source') {
						return true;
					}
					else {
						jQuery('#' + name).val(itemObj.val());
					}

					// set radio button value
					var radioObj = jQuery('input[name=' + name + ']');

					if (radioObj.attr('type') == 'radio') {
						radioObj.prop('checked', false);

						jQuery('#' + name + '_' + itemObj.val()).prop('checked', true);
					}
				}
			});
		}

		updateNewDCheckType(dcheckId);
	}

	function updateNewDCheckType(dcheckId) {
		var dcheckType = parseInt(jQuery('#type').val(), 10);

		var comRowTypes = {};
		comRowTypes[ZBX_SVC.snmpv1] = true;
		comRowTypes[ZBX_SVC.snmpv2] = true;

		var secNameRowTypes = {};
		secNameRowTypes[ZBX_SVC.snmpv3] = true;

		toggleInputs('newCheckPortsRow', (ZBX_SVC.icmp != dcheckType));
		toggleInputs('newCheckKeyRow', dcheckType == ZBX_SVC.agent);
		toggleInputs('new_check_snmp_oid_row', typeOfSnmp(dcheckType));
		toggleInputs('newCheckCommunityRow', isset(dcheckType, comRowTypes));
		toggleInputs('newCheckSecNameRow', isset(dcheckType, secNameRowTypes));
		toggleInputs('newCheckSecLevRow', isset(dcheckType, secNameRowTypes));
		toggleInputs('newCheckContextRow', isset(dcheckType, secNameRowTypes));

		// get old type
		var oldType = jQuery('#type').data('oldType');

		jQuery('#type').data('oldType', dcheckType);

		// type is changed
		if (ZBX_SVC.icmp != dcheckType && typeof oldType !== 'undefined' && dcheckType != oldType) {
			// reset values
			var snmpTypes = [ZBX_SVC.snmpv1, ZBX_SVC.snmpv2, ZBX_SVC.snmpv3],
				ignoreNames = ['druleid', 'name', 'ports', 'type'];

			if (jQuery.inArray(dcheckType, snmpTypes) !== -1 && jQuery.inArray(oldType, snmpTypes) !== -1) {
				// ignore value reset when changing type from snmp's
			}
			else {
				jQuery('#new_check_form input[type="text"]').each(function(i, item) {
					var itemObj = jQuery(item);

					if (jQuery.inArray(itemObj.attr('id'), ignoreNames) < 0) {
						itemObj.val('');
					}
				});

				// reset port to default
				jQuery('#ports').val(discoveryCheckDefaultPort(dcheckType));
			}
		}

		// set default port
		if (jQuery('#ports').val() == '') {
			jQuery('#ports').val(discoveryCheckDefaultPort(dcheckType));
		}

		updateNewDCheckSNMPType();
	}

	function updateNewDCheckSNMPType() {
		var dcheckType = parseInt(jQuery('#type').val(), 10),
			dcheckSecLevType = parseInt(jQuery('#snmpv3_securitylevel').val(), 10);

		var secNameRowTypes = {};
		secNameRowTypes[ZBX_SVC.snmpv3] = true;

		var showAuthProtocol = (isset(dcheckType, secNameRowTypes)
			&& (dcheckSecLevType == <?= ITEM_SNMPV3_SECURITYLEVEL_AUTHNOPRIV ?>
				|| dcheckSecLevType == <?= ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV ?>));
		var showAuthPass = (isset(dcheckType, secNameRowTypes)
			&& (dcheckSecLevType == <?= ITEM_SNMPV3_SECURITYLEVEL_AUTHNOPRIV ?>
				|| dcheckSecLevType == <?= ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV ?>));
		var showPrivProtocol = (isset(dcheckType, secNameRowTypes)
			&& dcheckSecLevType == <?= ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV ?>);
		var showPrivPass = (isset(dcheckType, secNameRowTypes)
			&& dcheckSecLevType == <?= ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV ?>);

		toggleInputs('newCheckAuthProtocolRow', showAuthProtocol);
		toggleInputs('newCheckAuthPassRow', showAuthPass);
		toggleInputs('newCheckPrivProtocolRow', showPrivProtocol);
		toggleInputs('newCheckPrivPassRow', showPrivPass);
	}

	/**
	 * Validates discovery check.
	 *
	 * @param {string} dcheckId            Discovery rule check id.
	 * @param {HTMLElement} trigger_elmnt  Element that triggered this action.
	 */
	function saveNewDCheckForm(dcheckId, trigger_elmnt) {
		var dCheck = jQuery('#new_check_form :input:enabled').serializeJSON();
		if (typeof dCheck.snmp_oid != 'undefined') {
			dCheck.key_ = dCheck.snmp_oid;
			delete dCheck.snmp_oid;
		}

		// get check id
		dCheck.dcheckid = (typeof dcheckId === 'undefined') ? getUniqueId() : dcheckId;

		// check for duplicates
		for (var zbxDcheckId in ZBX_CHECKLIST) {
			if (typeof dcheckId === 'undefined' || (typeof dcheckId !== 'undefined') && dcheckId != zbxDcheckId) {
				if ((typeof dCheck['key_'] === 'undefined' || ZBX_CHECKLIST[zbxDcheckId]['key_'] === dCheck['key_'])
						&& (typeof dCheck['type'] === 'undefined'
							|| ZBX_CHECKLIST[zbxDcheckId]['type'] === dCheck['type'])
						&& (typeof dCheck['ports'] === 'undefined'
							|| ZBX_CHECKLIST[zbxDcheckId]['ports'] === dCheck['ports'])
						&& (typeof dCheck['snmp_community'] === 'undefined'
							|| ZBX_CHECKLIST[zbxDcheckId]['snmp_community'] === dCheck['snmp_community'])
						&& (typeof dCheck['snmpv3_authprotocol'] === 'undefined'
							|| ZBX_CHECKLIST[zbxDcheckId]['snmpv3_authprotocol'] === dCheck['snmpv3_authprotocol'])
						&& (typeof dCheck['snmpv3_authpassphrase'] === 'undefined'
							|| ZBX_CHECKLIST[zbxDcheckId]['snmpv3_authpassphrase'] === dCheck['snmpv3_authpassphrase'])
						&& (typeof dCheck['snmpv3_privprotocol'] === 'undefined'
							|| ZBX_CHECKLIST[zbxDcheckId]['snmpv3_privprotocol'] === dCheck['snmpv3_privprotocol'])
						&& (typeof dCheck['snmpv3_privpassphrase'] === 'undefined'
							|| ZBX_CHECKLIST[zbxDcheckId]['snmpv3_privpassphrase'] === dCheck['snmpv3_privpassphrase'])
						&& (typeof dCheck['snmpv3_securitylevel'] === 'undefined'
							|| ZBX_CHECKLIST[zbxDcheckId]['snmpv3_securitylevel'] === dCheck['snmpv3_securitylevel'])
						&& (typeof dCheck['snmpv3_securityname'] === 'undefined'
							|| ZBX_CHECKLIST[zbxDcheckId]['snmpv3_securityname'] === dCheck['snmpv3_securityname'])
						&& (typeof dCheck['snmpv3_contextname'] === 'undefined'
							|| ZBX_CHECKLIST[zbxDcheckId]['snmpv3_contextname'] === dCheck['snmpv3_contextname'])) {

					overlayDialogue({
						'title': <?= CJs::encodeJson(_('Discovery check error')) ?>,
						'content': jQuery('<span>').text(<?= CJs::encodeJson(_('Check already exists.')) ?>),
						'buttons': [
							{
								'title': <?= CJs::encodeJson(_('Cancel')) ?>,
								'cancel': true,
								'focused': true,
								'action': function() {}
							}
						]
					}, trigger_elmnt);

					return null;
				}
			}
		}

		// validate
		var validationErrors = [],
			ajaxChecks = {
				ajaxaction: 'validate',
				ajaxdata: []
			};

		switch (parseInt(dCheck.type, 10)) {
			case ZBX_SVC.agent:
				ajaxChecks.ajaxdata.push({
					field: 'itemKey',
					value: dCheck.key_
				});
				break;
			case ZBX_SVC.snmpv1:
			case ZBX_SVC.snmpv2:
				if (dCheck.snmp_community == '') {
					validationErrors.push(<?= CJs::encodeJson(_('Incorrect SNMP community.')) ?>);
				}
			case ZBX_SVC.snmpv3:
				if (dCheck.key_ == '') {
					validationErrors.push(<?= CJs::encodeJson(_('Incorrect SNMP OID.')) ?>);
				}
				break;
		}

		if (dCheck.type != ZBX_SVC.icmp) {
			ajaxChecks.ajaxdata.push({
				field: 'port',
				value: dCheck.ports
			});
		}

		var jqxhr;

		if (ajaxChecks.ajaxdata.length > 0) {
			jQuery('#add_new_dcheck').prop('disabled', true);

			var url = new Curl();
			jqxhr = jQuery.ajax({
				url: url.getPath() + '?output=ajax&sid=' + url.getArgument('sid'),
				data: ajaxChecks,
				dataType: 'json',
				success: function(result) {
					if (!result.result) {
						jQuery.each(result.errors, function(i, val) {
							validationErrors.push(val.error);
						});
					}
				},
				error: function() {
					overlayDialogue({
						'title': <?= CJs::encodeJson(_('Discovery check error')) ?>,
						'content': jQuery('<span>').text(<?= CJs::encodeJson(
							_('Cannot validate discovery check: invalid request or connection to Zabbix server failed.')
						) ?>),
						'buttons': [
							{
								'title': <?= CJs::encodeJson(_('Cancel')) ?>,
								'cancel': true,
								'focused': true,
								'action': function() {}
							}
						]
					}, trigger_elmnt);
					jQuery('#add_new_dcheck').prop('disabled', false);
				}
			});
		}

		jQuery.when(jqxhr).done(function() {
			jQuery('#add_new_dcheck').prop('disabled', false);

			if (validationErrors.length) {
				var content = jQuery('<span>');

				for (var i = 0; i < validationErrors.length; i++) {
					if (content.html() !== '') {
						content.append(jQuery('<br>'));
					}
					content.append(jQuery('<span>').text(validationErrors[i]));
				}

				overlayDialogue({
					'title': <?= CJs::encodeJson(_('Discovery check error')) ?>,
					'content': content,
					'buttons': [
						{
							'title': <?= CJs::encodeJson(_('Cancel')) ?>,
							'cancel': true,
							'focused': true,
							'action': function() {}
						}
					]
				}, trigger_elmnt);
			}
			else {
				dCheck.name = jQuery('#type :selected').text();

				if (typeof dCheck.ports !== 'undefined' && dCheck.ports != discoveryCheckDefaultPort(dCheck.type)) {
					dCheck.name += ' (' + dCheck.ports + ')';
				}
				if (dCheck.key_) {
					dCheck.name += ' "' + dCheck.key_ + '"';
				}

				dCheck.host_source = jQuery('[name=host_source]:checked:not([data-id])').val()
					|| '<?= ZBX_DISCOVERY_DNS ?>';
				dCheck.name_source = jQuery('[name=name_source]:checked:not([data-id])').val()
					|| '<?= ZBX_DISCOVERY_UNSPEC ?>';

				addPopupValues([dCheck]);

				jQuery('#new_check_form').remove();
			}
		});
	}

	jQuery(document).ready(function() {
		addPopupValues(<?= zbx_jsvalue(array_values($this->data['drule']['dchecks'])) ?>);

		jQuery("input:radio[name='uniqueness_criteria'][value=<?= zbx_jsvalue($this->data['drule']['uniqueness_criteria']) ?>]").attr('checked', 'checked');
		jQuery("input:radio[name='host_source'][value=<?= zbx_jsvalue($this->data['drule']['host_source']) ?>]").attr('checked', 'checked');
		jQuery("input:radio[name='name_source'][value=<?= zbx_jsvalue($this->data['drule']['name_source']) ?>]").attr('checked', 'checked');

		jQuery('#newCheck').click(showNewCheckForm);
		jQuery('#clone').click(function() {
			jQuery('#update')
				.text(<?= CJs::encodeJson(_('Add')) ?>)
				.attr({id: 'add', name: 'add'});
			jQuery('#druleid, #delete, #clone').remove();
			jQuery('#form').val('clone');
			jQuery('#name').focus();
		});

		jQuery('#host_source,#name_source').on('change', 'input', function() {
			var elm = jQuery(this),
				name = elm.attr('name');

			if (elm.data('id')) {
				jQuery('[name^=dchecks][name$="[' + name + ']"]')
					.val((name === 'name_source') ? <?= ZBX_DISCOVERY_UNSPEC ?> : <?= ZBX_DISCOVERY_DNS ?>);
				jQuery('[name="dchecks[' + elm.data('id') + '][' + name + ']"]').val(<?= ZBX_DISCOVERY_VALUE ?>);
			}
			else {
				jQuery('[name^=dchecks][name$="[' + name + ']"]').val(elm.val());
			}
		});
	});
</script>
