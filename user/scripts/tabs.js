/*
 * PECI - A simplified interface for non-superadmin LimeSurvey users.
 * Copyright (C) 2012 Haute École de Gestion de Genève
 * Javier Belmonte <javier.belmonte@hesge.ch>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This function takes the DIV with ID "wrapper" and puts it into
 * a tab we have created in the interface.
 * @param targetTabId
 */
function putWrapperIntoTab(targetTabId) {
	var tab = $('#' + targetTabId);
	var wrapper = $('#wrapper');
	
	wrapper.appendTo(tab);
}

function createTabAndContent(name, idPrefix) {
	var usabilityTabNameBar = $('#usabilityTabNameBar');
	var usabilityTabContainer = $('#usabilityTabContainer');
	
	var theoryTabName = createSingleTabName(name, idPrefix + 'TabName');
	usabilityTabNameBar.append(theoryTabName);
	var theoryTabContent = createSingleTabContent(idPrefix + 'TabContent');
	usabilityTabContainer.append(theoryTabContent);
}

function createSingleTabName(name, id) {
	var singleTabName = $('<div>' + name + '</div>');
	singleTabName.attr("id", id);
	singleTabName.addClass('usabilityTabName');
	singleTabName.attr("onClick", 'javascript: selectTab("' + id + '")');
	
	return singleTabName;
}

function createSingleTabContent(id) {
	var singleTabContent = $('<div></div>');
	singleTabContent.attr("id", id);
	singleTabContent.addClass('usabilityTabContent');
	singleTabContent.hide();
	
	return singleTabContent;
}

function addContentToTab(id, text) {
	var tabContentDiv = $('#' + id + 'TabContent');
	var tabContent = $(decodeURIComponent(text + ''));
	
	tabContentDiv.append(tabContent);
}

function selectTab(targetTabId) {
	$('.usabilityTabName').removeClass('usabilityTabSelected');
	$('#' + targetTabId).addClass('usabilityTabSelected');
	
	$('.usabilityTabContent').hide();
	$('#' + targetTabId.substr(0, targetTabId.length-7) +  'TabContent').show();
	
	
	$.cookie('peci_tab_selected', targetTabId);
}
