<?php
/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
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


$page_url = (new CUrl('zabbix.php'))->setArgument('action', 'image.list');
$widget = (new CWidget())
	->setTitle(_('Images'))
	->setControls((new CTag('nav', true,
		(new CForm())
			->cleanItems()
			->addItem((new CList())
				->addItem(makeAdministrationGeneralMenu((new CUrl('zabbix.php'))
					->setArgument('action', 'image.list')
					->getUrl()
				))
			)
			->setAction($page_url->getUrl())
			->addItem((new CList())
				->addItem([
					new CLabel(_('Type'), 'imagetype'),
					(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
					new CComboBox('imagetype', $page_url
							->setArgument('imagetype', $data['imagetype'])
							->getUrl(), 'redirect(this.options[this.selectedIndex].value);', [
						$page_url
							->setArgument('imagetype', IMAGE_TYPE_ICON)
							->getUrl() => _('Icon'),
						$page_url
							->setArgument('imagetype', IMAGE_TYPE_BACKGROUND)
							->getUrl() => _('Background'),
					])
				])
				->addItem(
					(new CButton(null, ($data['imagetype'] == IMAGE_TYPE_ICON)
						? _('Create icon')
						: _('Create background')
					))->onClick(sprintf('javascript: document.location="%s";', (new CUrl('zabbix.php'))
							->setArgument('action', 'image.edit')
							->setArgument('imagetype', $data['imagetype'])
							->getUrl()
					))
				)
			)
		))
			->setAttribute('aria-label', _('Content controls'))
	);

if (!$data['images']) {
	$widget->addItem(new CTableInfo());
}
else {
	$image_table = (new CDiv())
		->addClass(ZBX_STYLE_TABLE)
		->addClass(ZBX_STYLE_ADM_IMG);

	$count = 0;
	$image_row = (new CDiv())->addClass(ZBX_STYLE_ROW);
	$edit_url = (new Curl('zabbix.php'))->setArgument('action', 'image.edit');

	foreach ($data['images'] as $image) {
		$img = ($image['imagetype'] == IMAGE_TYPE_BACKGROUND)
			? new CLink(
				new CImg('imgstore.php?width=200&height=200&iconid='.$image['imageid'], 'no image'),
				'image.php?imageid='.$image['imageid']
			)
			: new CImg('imgstore.php?iconid='.$image['imageid'], 'no image');

		$edit_url->setArgument('imageid', $image['imageid']);

		$image_row->addItem(
			(new CDiv())
				->addClass(ZBX_STYLE_CELL)
				->addItem([$img, BR(), new CLink($image['name'], $edit_url->getUrl())])
		);

		if ((++$count % 5) == 0) {
			$image_table->addItem($image_row);
			$image_row = (new CDiv())->addClass(ZBX_STYLE_ROW);
		}
	}

	if (($count % 5) != 0) {
		$image_table->addItem($image_row);
	}

	$widget->addItem(
		(new CForm())->addItem(
			(new CTabView())->addTab('image', null, $image_table)
		)
	);
}

$widget->show();
