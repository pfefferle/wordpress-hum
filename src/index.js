/* global humEditorObject */
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { ClipboardButton, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

const HumGutenbergShortlinkPanel = () => {
	const [ hasCopied, setHasCopied ] = useState( false );

	return (
		<PluginDocumentSettingPanel
			name="shortlink-panel"
			title="Shortlink"
			className="shortlink-panel"
		>
			<TextControl
				label={ humEditorObject.inputLabel }
				hideLabelFromVision="true"
				value={ humEditorObject.shortlink }
				disabled
			/>
			<ClipboardButton
				isPrimary
				text={ humEditorObject.shortlink }
				onCopy={ () => setHasCopied( true ) }
				onFinishCopy={ () => setHasCopied( false ) }
			>
				{ hasCopied ? humEditorObject.copyButtonCopiedLabel : humEditorObject.copyButtonLabel }
			</ClipboardButton>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'hum-gutenberg-shortlink-panel', {
	render: HumGutenbergShortlinkPanel
} );
