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

function createAllUsabilitTabNames() {
	var usabilityTabNameBar = $('#usabilityTabNameBar');
	
	var homeTabName = createSingleTabName('Home', 'homeUsabilityTabName');
	usabilityTabNameBar.append(homeTabName);
	
	var theoryTabName = createSingleTabName('Theory', 'theoryUsabilityTabName');
	usabilityTabNameBar.append(theoryTabName);
	
	var createTabName = createSingleTabName('Create your evaluation', 'createUsabilityTabName');
	usabilityTabNameBar.append(createTabName);
	
	var researchTabName = createSingleTabName('Research project', 'researchUsabilityTabName');
	usabilityTabNameBar.append(researchTabName);
	
	var contactTabName = createSingleTabName('Contact', 'contactUsabilityTabName');
	usabilityTabNameBar.append(contactTabName);
}

function createSingleTabName(name, id) {
	var singleTabName = $('<div>' + name + '</div>');
	singleTabName.attr("id", id);
	singleTabName.addClass('usabilityTabName');
	singleTabName.attr("onClick", 'javascript: selectTab("' + id + '")');
	
	return singleTabName;
}

function selectTab(targetTabId) {
	$('.usabilityTabName').removeClass('usabilityTabSelected');
	$('#' + targetTabId).addClass('usabilityTabSelected');
}