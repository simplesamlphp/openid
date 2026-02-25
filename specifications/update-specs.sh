#!/usr/bin/env bash

# Update the specifications

# Check if pandoc is installed
if ! command -v pandoc &> /dev/null
then
    echo "pandoc could not be found. Please install it to run this script."
    exit 1
fi

URLS=(
    # OpenID specifications
    "https://openid.net/specs/openid-4-verifiable-credential-issuance-1_0.html"
    "https://openid.net/specs/openid-federation-1_0.html"
    "https://openid.net/specs/openid-connect-core-1_0.html"
    "https://openid.net/specs/openid-connect-discovery-1_0.html"
    "https://openid.net/specs/openid-connect-rpinitiated-1_0.html"
    "https://openid.net/specs/openid-connect-frontchannel-1_0.html"
    "https://openid.net/specs/openid-connect-backchannel-1_0.html"
    # W3C specifications
    "https://www.w3.org/TR/vc-data-model-2.0/"
    "https://www.w3.org/TR/vc-jose-cose/"
    "https://www.w3.org/TR/vc-imp-guide/"
    "https://www.w3.org/TR/did-1.0/"
    # DIIP specifications
    "https://fidescommunity.github.io/DIIP/"
)

# For each of the specifications, fetch the content from the URL and save it
# to a local markdown file. Use `pandoc` to convert the HTML to markdown.
# The output file should be named after the last part of the URL, without the
# extension. For example, for the URL "https://example.com/specs/spec1.html",
# the output file should be "spec1.md".
# The output file should be saved in the "specifications" directory.

SPEC_DIR="$(dirname "$0")"

for URL in "${URLS[@]}"
do
    # Extract the filename without extension
    FILENAME=$(basename "$URL" .html)
    OUTPUT_FILE="$SPEC_DIR/$FILENAME.md"

    echo "Updating $FILENAME..."

    # Fetch and convert
    # -f html: from HTML
    # -t markdown: to Markdown, with additional extensions to clean up the output
    # -s: standalone
    # --wrap=none: optional, to avoid unnecessary line breaks if preferred
    
    PANDOC_FORMAT="markdown-fenced_divs-bracketed_spans-header_attributes-link_attributes-inline_code_attributes-raw_html-native_divs-native_spans"

    if curl -s "$URL" | pandoc -f html -t "$PANDOC_FORMAT" -s --wrap=none -o "$OUTPUT_FILE"; then
        echo "Successfully updated $OUTPUT_FILE"
    else
        echo "Failed to update $FILENAME"
    fi
done


