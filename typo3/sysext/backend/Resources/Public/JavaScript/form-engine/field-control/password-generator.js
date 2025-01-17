/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
import DocumentService from"@typo3/core/document-service.js";import SecurityUtility from"@typo3/core/security-utility.js";import FormEngineValidation from"@typo3/backend/form-engine-validation.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Notification from"@typo3/backend/notification.js";class PasswordGenerator{constructor(e){this.securityUtility=null,this.controlElement=null,this.humanReadableField=null,this.hiddenField=null,this.passwordRules=null,this.securityUtility=new SecurityUtility,DocumentService.ready().then((()=>{this.controlElement=document.getElementById(e),this.humanReadableField=document.querySelector('input[data-formengine-input-name="'+this.controlElement.dataset.itemName+'"]'),this.hiddenField=document.querySelector('input[name="'+this.controlElement.dataset.itemName+'"]'),this.passwordRules=JSON.parse(this.controlElement.dataset.passwordRules||"{}"),this.controlElement.addEventListener("click",this.generatePassword.bind(this))}))}generatePassword(e){e.preventDefault(),new AjaxRequest(TYPO3.settings.ajaxUrls.password_generate).post({passwordRules:this.passwordRules}).then((async e=>{const t=await e.resolve();!0===t.success?(this.humanReadableField.type="text",this.controlElement.dataset.allowEdit||(this.humanReadableField.disabled=!0,this.humanReadableField.readOnly=!0),this.humanReadableField.value=t.password,this.humanReadableField.dispatchEvent(new Event("change")),this.humanReadableField.value=this.hiddenField.value,FormEngineValidation.validateField(this.humanReadableField),FormEngineValidation.markFieldAsChanged(this.humanReadableField)):Notification.warning(t.message||"No password was generated")})).catch((()=>{Notification.error("Password could not be generated")}))}}export default PasswordGenerator;