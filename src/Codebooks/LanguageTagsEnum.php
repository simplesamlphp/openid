<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

/**
 * Represents a selection of common language tags based on RFC 5646.
 *
 * Note: This is NOT an exhaustive list of all possible valid language tags,
 * as defined by RFC 5646 and the IANA Language Subtag Registry.
 * Listing all tags is impractical due to the vast number and dynamic nature
 * of the registry. This enum contains a curated set of frequently used tags.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc5646
 * @see https://www.iana.org/assignments/language-subtag-registry/language-subtag-registry
 */
enum LanguageTagsEnum: string
{
    // English
    case En = 'en'; // English
    case EnUs = 'en-US'; // English, United States
    case EnGb = 'en-GB'; // English, United Kingdom
    case EnAu = 'en-AU'; // English, Australia
    case EnCa = 'en-CA'; // English, Canada
    case EnIe = 'en-IE'; // English, Ireland

    // Spanish
    case Es = 'es'; // Spanish
    case EsEs = 'es-ES'; // Spanish, Spain
    case EsMx = 'es-MX'; // Spanish, Mexico
    case EsAr = 'es-AR'; // Spanish, Argentina
    case Es419 = 'es-419'; // Spanish, Latin America and the Caribbean

    // French
    case Fr = 'fr'; // French
    case FrFr = 'fr-FR'; // French, France
    case FrCa = 'fr-CA'; // French, Canada
    case FrCh = 'fr-CH'; // French, Switzerland

    // German
    case De = 'de'; // German
    case DeDe = 'de-DE'; // German, Germany
    case DeAt = 'de-AT'; // German, Austria
    case DeCh = 'de-CH'; // German, Switzerland

    // Chinese
    case Zh = 'zh'; // Chinese
    case ZhHans = 'zh-Hans'; // Chinese, Simplified script
    case ZhHant = 'zh-Hant'; // Chinese, Traditional script
    case ZhCn = 'zh-CN'; // Chinese, China (implies Hans)
    case ZhHk = 'zh-HK'; // Chinese, Hong Kong (implies Hant)
    case ZhTw = 'zh-TW'; // Chinese, Taiwan (implies Hant)
    case ZhHansCn = 'zh-Hans-CN'; // Chinese, Simplified script, China
    case ZhHantTw = 'zh-Hant-TW'; // Chinese, Traditional script, Taiwan
    case ZhHantHk = 'zh-Hant-HK'; // Chinese, Traditional script, Hong Kong

    // Japanese
    case Ja = 'ja'; // Japanese
    case JaJp = 'ja-JP'; // Japanese, Japan

    // Portuguese
    case Pt = 'pt'; // Portuguese
    case PtPt = 'pt-PT'; // Portuguese, Portugal
    case PtBr = 'pt-BR'; // Portuguese, Brazil

    // Italian
    case It = 'it'; // Italian
    case ItIt = 'it-IT'; // Italian, Italy
    case ItCh = 'it-CH'; // Italian, Switzerland

    // Russian
    case Ru = 'ru'; // Russian
    case RuRu = 'ru-RU'; // Russian, Russia

    // Korean
    case Ko = 'ko'; // Korean
    case KoKr = 'ko-KR'; // Korean, Republic of Korea

    // Arabic
    case Ar = 'ar'; // Arabic
    case ArAe = 'ar-AE'; // Arabic, United Arab Emirates
    case ArSa = 'ar-SA'; // Arabic, Saudi Arabia
    case ArEg = 'ar-EG'; // Arabic, Egypt

    // Dutch
    case Nl = 'nl'; // Dutch
    case NlNL = 'nl-NL'; // Dutch, Netherlands
    case NlBE = 'nl-BE'; // Dutch, Belgium

    // Hindi
    case Hi = 'hi'; // Hindi
    case HiIN = 'hi-IN'; // Hindi, India

    // Swedish
    case Sv = 'sv'; // Swedish
    case SvSE = 'sv-SE'; // Swedish, Sweden
    case SvFI = 'sv-FI'; // Swedish, Finland

    // Norwegian
    case No = 'no'; // Norwegian (macro language)
    case Nb = 'nb'; // Norwegian Bokmål
    case Nn = 'nn'; // Norwegian Nynorsk
    case NbNO = 'nb-NO'; // Norwegian Bokmål, Norway
    case NnNO = 'nn-NO'; // Norwegian Nynorsk, Norway

    // Danish
    case Da = 'da'; // Danish
    case DaDK = 'da-DK'; // Danish, Denmark

    // Finnish
    case Fi = 'fi'; // Finnish
    case FiFI = 'fi-FI'; // Finnish, Finland

    // Polish
    case Pl = 'pl'; // Polish
    case PlPL = 'pl-PL'; // Polish, Poland

    // Turkish
    case Tr = 'tr'; // Turkish
    case TrTR = 'tr-TR'; // Turkish, Turkey

    // Czech
    case Cs = 'cs'; // Czech
    case CsCZ = 'cs-CZ'; // Czech, Czech Republic

    // Hungarian
    case Hu = 'hu'; // Hungarian
    case HuHU = 'hu-HU'; // Hungarian, Hungary

    // Greek
    case El = 'el'; // Greek
    case ElGR = 'el-GR'; // Greek, Greece

    // Hebrew
    case He = 'he'; // Hebrew
    case HeIL = 'he-IL'; // Hebrew, Israel

    // Thai
    case Th = 'th'; // Thai
    case ThTH = 'th-TH'; // Thai, Thailand

    // Add other common or application-specific tags as needed...
}
