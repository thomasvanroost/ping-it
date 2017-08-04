<?php

// Copyright (C) 2010-2016 Combodo SARL
//
//   This file is part of iTop.
//
//   iTop is free software; you can redistribute it and/or modify	
//   it under the terms of the GNU Affero General Public License as published by
//   the Free Software Foundation, either version 3 of the License, or
//   (at your option) any later version.
//
//   iTop is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU Affero General Public License for more details.
//
//   You should have received a copy of the GNU Affero General Public License
//   along with iTop. If not, see <http://www.gnu.org/licenses/>

namespace Combodo\iTop\Renderer\Bootstrap\FieldRenderer;

use \utils;
use \Dict;
use \UserRights;
use \InlineImage;
use \DBObjectSet;
use \MetaModel;
use \Combodo\iTop\Renderer\FieldRenderer;
use \Combodo\iTop\Renderer\RenderingOutput;
use \Combodo\iTop\Form\Field\LinkedSetField;

/**
 * Description of BsLinkedSetFieldRenderer
 *
 * @author Guillaume Lajarige <guillaume.lajarige@combodo.com>
 */
class BsLinkedSetFieldRenderer extends FieldRenderer
{

	/**
	 * Returns a RenderingOutput for the FieldRenderer's Field
	 *
	 * @return \Combodo\iTop\Renderer\RenderingOutput
	 */
	public function Render()
	{
		$oOutput = new RenderingOutput();
		$sFieldMandatoryClass = ($this->oField->GetMandatory()) ? 'form_mandatory' : '';
		// Vars to build the table
		$sAttributesToDisplayAsJson = json_encode($this->oField->GetAttributesToDisplay());
		$sAttCodesToDisplayAsJson = json_encode($this->oField->GetAttributesToDisplay(true));
		$aItems = array();
		$aItemIds = array();
		$this->PrepareItems($aItems, $aItemIds);
		$sItemsAsJson = json_encode($aItems);
		$sItemIdsAsJson = htmlentities(json_encode($aItemIds), ENT_QUOTES, 'UTF-8');
		
		if (!$this->oField->GetHidden())
		{
			// Rendering field
			$sIsEditable = ($this->oField->GetReadOnly()) ? 'false' : 'true';
			$sCollapseTogglerVisibleClass = 'glyphicon-menu-down';
			$sCollapseTogglerHiddenClass = 'glyphicon-menu-down collapsed';
			$sCollapseTogglerId = 'form_linkedset_toggler_' . $this->oField->GetGlobalId();
			$sFieldWrapperId = 'form_linkedset_wrapper_' . $this->oField->GetGlobalId();
			$oOutput->AddHtml('<div class="form-group ' . $sFieldMandatoryClass . '">');
			if ($this->oField->GetLabel() !== '')
			{
				$oOutput->AddHtml('<label for="' . $this->oField->GetGlobalId() . '" class="control-label">')
					->AddHtml($this->oField->GetLabel(), true)
					->AddHtml('<a id="' . $sCollapseTogglerId . '" class="form_linkedset_toggler" data-toggle="collapse" href="#' . $sFieldWrapperId . '" aria-expanded="false" aria-controls="' . $sFieldWrapperId . '">')
					->AddHtml('<span class="text">' . count($aItemIds) . '</span>')
					->AddHtml('<span class="glyphicon ' . $sCollapseTogglerHiddenClass . '"></>')
					->AddHtml('</a>')
					->AddHtml('</label>');
			}
			$oOutput->AddHtml('<div class="help-block"></div>');

			// Rendering table
			// - Vars
			$sTableId = 'table_' . $this->oField->GetGlobalId();
			// - Output
			$oOutput->AddHtml(
<<<EOF
				<div class="form_linkedset_wrapper collapse" id="{$sFieldWrapperId}">
					<div class="row">
						<div class="col-xs-12">
							<input type="hidden" id="{$this->oField->GetGlobalId()}" name="{$this->oField->GetId()}" value="{$sItemIdsAsJson}" />
							<table id="{$sTableId}" data-field-id="{$this->oField->GetId()}" class="table table-striped table-bordered responsive" cellspacing="0" width="100%">
								<tbody>
								</tbody>
							</table>
						</div>
					</div>
EOF
			);

			// Rendering table widget
			// - Vars
			$sEmptyTableLabel = htmlentities(Dict::S(($this->oField->GetReadOnly()) ? 'Portal:Datatables:Language:EmptyTable' : 'UI:Message:EmptyList:UseAdd'), ENT_QUOTES, 'UTF-8');
			$sLabelGeneralCheckbox = htmlentities(Dict::S('Core:BulkExport:CheckAll') . ' / ' . Dict::S('Core:BulkExport:UncheckAll'), ENT_QUOTES, 'UTF-8');
			$sSelectionOptionHtml = ($this->oField->GetReadOnly()) ? 'false' : '{"style": "multi"}';
			$sSelectionInputGlobalHtml = ($this->oField->GetReadOnly()) ? '' : '<span class="row_input"><input type="checkbox" id="' . $this->oField->GetGlobalId() . '_check_all" name="' . $this->oField->GetGlobalId() . '_check_all" title="' . $sLabelGeneralCheckbox . '" /></span>';
			$sSelectionInputHtml = ($this->oField->GetReadOnly()) ? '' : '<span class="row_input"><input type="checkbox" name="' . $this->oField->GetGlobalId() . '" /></span>';
			// - Output
			$oOutput->AddJs(
<<<EOF
				// Collapse handlers
				// - Collapsing by default to optimize form space
				// It would be better to be able to construct the widget as collapsed, but in this ase, datatables thinks the container is very small and therefore renders the table as if it was in microbox.
				$('#{$sFieldWrapperId}').collapse({toggle: false});
				// - Change toggle icon class
				$('#{$sFieldWrapperId}').on('shown.bs.collapse', function(){
					// Creating the table if null (first expand). If we create it on start, it will be displayed as if it was in a micro screen due to the div being "display: none;"
					if(oTable_{$this->oField->GetGlobalId()} === undefined)
					{
						buildTable_{$this->oField->GetGlobalId()}();
					}
				})
				.on('show.bs.collapse', function(){
					$('#{$sCollapseTogglerId} > span.glyphicon').removeClass('{$sCollapseTogglerHiddenClass}').addClass('{$sCollapseTogglerVisibleClass}');
				})
				.on('hide.bs.collapse', function(){
					$('#{$sCollapseTogglerId} > span.glyphicon').removeClass('{$sCollapseTogglerVisibleClass}').addClass('{$sCollapseTogglerHiddenClass}');
				});

				// Places a loader in the empty datatables
				$('#{$sTableId} > tbody').html('<tr><td class="datatables_overlay" colspan="100">' + $('#page_overlay').html() + '</td></tr>');

				// Prepares data for datatables
				var oColumnProperties_{$this->oField->GetGlobalId()} = {$sAttributesToDisplayAsJson};
				var oRawDatas_{$this->oField->GetGlobalId()} = {$sItemsAsJson};
				var oTable_{$this->oField->GetGlobalId()};
				var oSelectedItems_{$this->oField->GetGlobalId()} = {};

				var getColumnsDefinition_{$this->oField->GetGlobalId()} = function()
				{
					var aColumnsDefinition = [];

					if({$sIsEditable})
					{
						aColumnsDefinition.push({
								"width": "auto",
								"searchable": false,
								"sortable": false,
								"title": '{$sSelectionInputGlobalHtml}',
								"type": "html",
								"data": "",
								"render": function(data, type, row)
								{
									var oCheckboxElem = $('{$sSelectionInputHtml}');
									oCheckboxElem.find(':input').attr('data-object-id', row.id).attr('data-target-object-id', row.target_id);
									return oCheckboxElem.prop('outerHTML');
								}
						});
					}

					for(sKey in oColumnProperties_{$this->oField->GetGlobalId()})
					{
						// Level main column
						aColumnsDefinition.push({
							"width": "auto",
							"searchable": true,
							"sortable": true,
							"title": oColumnProperties_{$this->oField->GetGlobalId()}[sKey],
							"defaultContent": "",
							"type": "html",
							"data": "attributes."+sKey+".att_code",
							"render": function(data, type, row){
								var cellElem;

								// Preparing the cell data
								if(row.attributes[data].url !== undefined)
								{
									cellElem = $('<a></a>');
									cellElem.attr('target', '_blank').attr('href', row.attributes[data].url);
								}
								else
								{
									cellElem = $('<span></span>');
								}
								cellElem.html('<span>' + row.attributes[data].value + '</span>');

								return cellElem.prop('outerHTML');
							},
						});
					}

					return aColumnsDefinition;
				};

				// Helper to build the datatable
				// Note : Those options should be externalized in an library so we can use them on any DataTables for the portal.
				// We would just have to override / complete the necessary elements
				var buildTable_{$this->oField->GetGlobalId()} = function()
				{
					var iDefaultOrderColumnIndex = ({$sIsEditable}) ? 1 : 0;

					// Instanciates datatables
					oTable_{$this->oField->GetGlobalId()} = $('#{$sTableId}').DataTable({
						"language": {
							"emptyTable":	  "{$sEmptyTableLabel}"
						},
						"displayLength": -1,
						"scrollY": "300px",
						"scrollCollapse": true,
						"order": [[iDefaultOrderColumnIndex, "asc"]],
						"dom": 't',
						"columns": getColumnsDefinition_{$this->oField->GetGlobalId()}(),
						"select": {$sSelectionOptionHtml},
						"rowId": "id",
						"data": oRawDatas_{$this->oField->GetGlobalId()},
					});
						
					// Handles items selection/deselection
					// - Directly on the table
					oTable_{$this->oField->GetGlobalId()}.off('select').on('select', function(oEvent, dt, type, indexes){
						var aData = oTable_{$this->oField->GetGlobalId()}.rows(indexes).data().toArray();

						// Checking input
						$('#{$sTableId} tbody tr[role="row"].selected td:first-child input').prop('checked', true);
						// Saving values in temp array
						for(var i in aData)
						{
							var iItemId = aData[i].id;
							if(!(iItemId in oSelectedItems_{$this->oField->GetGlobalId()}))
							{
								oSelectedItems_{$this->oField->GetGlobalId()}[iItemId] = aData[i].name;
							}
						}
						// Updating remove button
						updateRemoveButtonState_{$this->oField->GetGlobalId()}();
					});
					oTable_{$this->oField->GetGlobalId()}.off('deselect').on('deselect', function(oEvent, dt, type, indexes){
						var aData = oTable_{$this->oField->GetGlobalId()}.rows(indexes).data().toArray();

						// Checking input
						$('#{$sTableId} tbody tr[role="row"]:not(.selected) td:first-child input').prop('checked', false);
						// Saving values in temp array
						for(var i in aData)
						{
							var iItemId = aData[i].id;
							if(iItemId in oSelectedItems_{$this->oField->GetGlobalId()})
							{
								delete oSelectedItems_{$this->oField->GetGlobalId()}[iItemId];
							}
						}
						// Unchecking global checkbox
						$('#{$this->oField->GetGlobalId()}_check_all').prop('checked', false);
						// Updating remove button
						updateRemoveButtonState_{$this->oField->GetGlobalId()}();
					});
					// - From the global button
					$('#{$this->oField->GetGlobalId()}_check_all').off('click').on('click', function(oEvent){
						if($(this).prop('checked'))
						{
							oTable_{$this->oField->GetGlobalId()}.rows().select();
						}
						else
						{
							oTable_{$this->oField->GetGlobalId()}.rows().deselect();
						}
						updateRemoveButtonState_{$this->oField->GetGlobalId()}();
					});
				};
EOF
			);

			// Attaching JS widget
			$sObjectInformationsUrl = $this->oField->GetInformationEndpoint();
			$oOutput->AddJs(
<<<EOF
				$("[data-field-id='{$this->oField->GetId()}'][data-form-path='{$this->oField->GetFormPath()}']").portal_form_field({
					'validators': {$this->GetValidatorsAsJson()},
					'get_current_value_callback': function(me, oEvent, oData){
						var value = null;

						// Retrieving JSON value as a string and not an object
						//
						// Note : The value is passed as a string instead of an array because the attribute would not be included in the posted data when empty.
						// Which was an issue when deleting all objects from linkedset
						//
						// Old code : value = JSON.parse(me.element.find('#{$this->oField->GetGlobalId()}').val());
						value = me.element.find('#{$this->oField->GetGlobalId()}').val();

						return value;
					},
					'set_current_value_callback': function(me, oEvent, oData){
						// When we have data (meaning that we picked objects from search)
						if(oData !== undefined && Object.keys(oData.values).length > 0)
						{
							// Showing loader while retrieving informations
							$('#page_overlay').fadeIn(200);

							// Retrieving new rows ids
							var aObjectIds = Object.keys(oData.values);

							// Retrieving rows informations so we can add them
							$.post(
								'{$sObjectInformationsUrl}',
								{
									sObjectClass: '{$this->oField->GetTargetClass()}',
									aObjectIds: aObjectIds,
									aObjectAttCodes: $sAttCodesToDisplayAsJson
								},
								function(oData){
									// Updating datatables
									if(oData.items !== undefined)
									{
										for(var i in oData.items)
										{
											// Adding target item id information
											oData.items[i].target_id = oData.items[i].id;
											// Adding item to table only if it's not already there
											if($('#{$sTableId} tr[role="row"] > td input[data-target-object-id="' + oData.items[i].target_id + '"], #{$sTableId} tr[role="row"] > td input[data-target-object-id="' + (oData.items[i].target_id*-1) + '"]').length === 0)
											{
												// Making id negative in order to recognize it when persisting
												oData.items[i].id = -1 * parseInt(oData.items[i].id);
												oTable_{$this->oField->GetGlobalId()}.row.add(oData.items[i]);
											}
										}
										oTable_{$this->oField->GetGlobalId()}.draw();
									}
								}
							)
							.done(function(oData){
								// Updating hidden field
								var aData = oTable_{$this->oField->GetGlobalId()}.rows().data().toArray();
								var aObjectIds = [];

								for(var i in aData)
								{
									aObjectIds.push({id: aData[i].id});
								}

								$('#{$this->oField->GetGlobalId()}').val(JSON.stringify(aObjectIds));
								// Updating items count
								updateItemCount_{$this->oField->GetGlobalId()}();
								// Updating global checkbox
								$('#{$this->oField->GetGlobalId()}_check_all').prop('checked', false);
							})
							.always(function(oData){
								// Hiding loader
								$('#page_overlay').fadeOut(200);
							});
						}
						// We come from a button
						else
						{
							// Updating hidden field
							var aData = oTable_{$this->oField->GetGlobalId()}.rows().data().toArray();
							var aObjectIds = [];

							for(var i in aData)
							{
								aObjectIds.push({id: aData[i].id});
							}

							$('#{$this->oField->GetGlobalId()}').val(JSON.stringify(aObjectIds));
							// Updating items count
							updateItemCount_{$this->oField->GetGlobalId()}();
							// Updating global checkbox
							$('#{$this->oField->GetGlobalId()}_check_all').prop('checked', false);
						}
					}
				});
EOF
			);

			// Additional features if in edition mode
			if (!$this->oField->GetReadOnly())
			{
				// Rendering table
				// - Vars
				$sButtonRemoveId = 'btn_remove_' . $this->oField->GetGlobalId();
				$sButtonAddId = 'btn_add_' . $this->oField->GetGlobalId();
				$sLabelRemove = Dict::S('UI:Button:Remove');
				$sLabelAdd = Dict::S('UI:Button:AddObject');
				// - Output
				$oOutput->AddHtml(
<<<EOF
					<div class="row">
						<div class="col-xs-12">
							<div class="btn-group" role="group">
								<button type="button" class="btn btn-sm btn-danger" id="{$sButtonRemoveId}" title="{$sLabelRemove}" disabled><span class="glyphicon glyphicon-minus"></span></button>
								<button type="button" class="btn btn-sm btn-default" id="{$sButtonAddId}" title="{$sLabelAdd}"><span class="glyphicon glyphicon-plus"></span></button>
							</div>
						</div>
					</div>
EOF
				);

				// Rendering table widget
				// - Vars
				$sAddButtonEndpoint = str_replace('-sMode-', 'from-attribute', $this->oField->GetSearchEndpoint());
				// - Output
				$oOutput->AddJs(
	<<<EOF
					// Handles items selection/deselection
					// - Remove button state handler
					var updateRemoveButtonState_{$this->oField->GetGlobalId()} = function()
					{
						var bIsDisabled = (Object.keys(oSelectedItems_{$this->oField->GetGlobalId()}).length == 0);
						$('#{$sButtonRemoveId}').prop('disabled', bIsDisabled);
					};
					// - Item count state handler
					var updateItemCount_{$this->oField->GetGlobalId()} = function()
					{
						$('#{$sCollapseTogglerId} > .text').text( oTable_{$this->oField->GetGlobalId()}.rows().count() );
					};

					// Handles items remove/add
					$('#{$sButtonRemoveId}').off('click').on('click', function(){
						// Removing items from table
						oTable_{$this->oField->GetGlobalId()}.rows({selected: true}).remove().draw();
						// Resetting selected items
						oSelectedItems_{$this->oField->GetGlobalId()} = {};
						// Updating form value
						$("[data-field-id='{$this->oField->GetId()}'][data-form-path='{$this->oField->GetFormPath()}']").triggerHandler('set_current_value');
						// Updating global checkbox state
						$('#{$this->oField->GetGlobalId()}_check_all').prop('checked', false);
						// Updating remove button
						updateRemoveButtonState_{$this->oField->GetGlobalId()}();
					});
					$('#{$sButtonAddId}').off('click').on('click', function(){
						// Preparing current values
						var aObjectIdsToIgnore = [];
						$('#{$sTableId} tr[role="row"] > td input[data-target-object-id]').each(function(iIndex, oElem){
							aObjectIdsToIgnore.push( $(oElem).attr('data-target-object-id') );
						});
						// Creating a new modal
						var oModalElem;
						if($('.modal[data-source-element="{$sButtonAddId}"]').length === 0)
						{
							oModalElem = $('#modal-for-all').clone();
							oModalElem.attr('id', '').attr('data-source-element', '{$sButtonAddId}').appendTo('body');
						}
						else
						{
							oModalElem = $('.modal[data-source-element="{$sButtonAddId}"]').first();
						}
						// Resizing to small modal
						oModalElem.find('.modal-dialog').removeClass('modal-sm').addClass('modal-lg');
						// Loading content
						oModalElem.find('.modal-content').html($('#page_overlay .overlay_content').html());
						oModalElem.find('.modal-content').load(
							'{$sAddButtonEndpoint}',
							{
								sFormPath: '{$this->oField->GetFormPath()}',
								sFieldId: '{$this->oField->GetId()}',
								aObjectIdsToIgnore : aObjectIdsToIgnore
							}
						);
						oModalElem.modal('show');
					});
EOF
				);
			}
		}
		// ... and in hidden mode
		else
		{
			$oOutput->AddHtml('<input type="hidden" id="' . $this->oField->GetGlobalId() . '" name="' . $this->oField->GetId() . '" value="' . $sItemIdsAsJson . '" />');
		}

		// End of table rendering
		$oOutput->AddHtml('</div>');
		$oOutput->AddHtml('</div>');

		return $oOutput;
	}

	protected function PrepareItems(&$aItems, &$aItemIds)
	{
		$oValueSet = $this->oField->GetCurrentValue();
		$oValueSet->OptimizeColumnLoad(array($this->oField->GetTargetClass() => $this->oField->GetAttributesToDisplay(true)));
		while ($oItem = $oValueSet->Fetch())
		{
			// In case of indirect linked set, we must retrieve the remote object
			if ($this->oField->IsIndirect())
			{
				// Note : AllowAllData set to true here instead of checking scope's flag because we are displaying a value that has been set and validated
				$oRemoteItem = MetaModel::GetObject($this->oField->GetTargetClass(), $oItem->Get($this->oField->GetExtKeyToRemote()), true, true);
			}
			else
			{
				$oRemoteItem = $oItem;
			}

			$aItemProperties = array(
				'id' => $oItem->GetKey(),
				'target_id' => $oRemoteItem->GetKey(),
				'name' => $oItem->GetName(),
				'attributes' => array()
			);

			// Target object others attributes
			foreach ($this->oField->GetAttributesToDisplay(true) as $sAttCode)
			{
				if ($sAttCode !== 'id')
				{
					$aAttProperties = array(
						'att_code' => $sAttCode
					);

					$oAttDef = MetaModel::GetAttributeDef($this->oField->GetTargetClass(), $sAttCode);
					if ($oAttDef->IsExternalKey())
					{
						$aAttProperties['value'] = $oRemoteItem->Get($sAttCode . '_friendlyname');
					}
					else
					{
						$aAttProperties['value'] = $oAttDef->GetValueLabel($oRemoteItem->Get($sAttCode));
					}

					$aItemProperties['attributes'][$sAttCode] = $aAttProperties;
				}
			}
			
			$aItems[] = $aItemProperties;
			$aItemIds[] = array('id' => $oItem->GetKey());
		}
	}

}
