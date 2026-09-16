import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { ClipboardButton, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Document settings panel that shows the shortlink of the current post.
 *
 * @return {JSX.Element} The panel.
 */
const ShortlinkPanel = () => {
	const { shortlink } = window._humEditorObject;
	const [ hasCopied, setHasCopied ] = useState( false );

	return (
		<PluginDocumentSettingPanel
			name="shortlink-panel"
			title="Shortlink"
			className="shortlink-panel"
		>
			<TextControl
				label={ __( 'Shortlink', 'hum' ) }
				hideLabelFromVision="true"
				value={ shortlink }
				disabled
			/>
			<ClipboardButton
				isPrimary
				text={ shortlink }
				onCopy={ () => setHasCopied( true ) }
				onFinishCopy={ () => setHasCopied( false ) }
			>
				{ hasCopied ? __( 'Copied!', 'hum' ) : __( 'Copy link', 'hum' ) }
			</ClipboardButton>
		</PluginDocumentSettingPanel>
	);
};

export default ShortlinkPanel;
