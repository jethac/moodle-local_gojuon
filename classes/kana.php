<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_gojuon;

/**
 * Gojūon row definitions: row key => every leading character (hiragana and
 * katakana, voiced/semi-voiced/small variants included) that files under it.
 *
 * @package   local_gojuon
 * @copyright 2026 Jetha Chan
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class kana {
    /** @var array<string, array{label: string, chars: string[]}> */
    const ROWS = [
        'a'  => ['label' => 'あ', 'chars' => ['あ', 'ぁ', 'い', 'ぃ', 'う', 'ぅ', 'ゔ', 'え', 'ぇ', 'お', 'ぉ',
                                              'ア', 'ァ', 'イ', 'ィ', 'ウ', 'ゥ', 'ヴ', 'エ', 'ェ', 'オ', 'ォ']],
        'ka' => ['label' => 'か', 'chars' => ['か', 'が', 'き', 'ぎ', 'く', 'ぐ', 'け', 'げ', 'こ', 'ご', 'ゕ', 'ゖ',
                                              'カ', 'ガ', 'キ', 'ギ', 'ク', 'グ', 'ケ', 'ゲ', 'コ', 'ゴ', 'ヵ', 'ヶ']],
        'sa' => ['label' => 'さ', 'chars' => ['さ', 'ざ', 'し', 'じ', 'す', 'ず', 'せ', 'ぜ', 'そ', 'ぞ',
                                              'サ', 'ザ', 'シ', 'ジ', 'ス', 'ズ', 'セ', 'ゼ', 'ソ', 'ゾ']],
        'ta' => ['label' => 'た', 'chars' => ['た', 'だ', 'ち', 'ぢ', 'つ', 'っ', 'づ', 'て', 'で', 'と', 'ど',
                                              'タ', 'ダ', 'チ', 'ヂ', 'ツ', 'ッ', 'ヅ', 'テ', 'デ', 'ト', 'ド']],
        'na' => ['label' => 'な', 'chars' => ['な', 'に', 'ぬ', 'ね', 'の', 'ナ', 'ニ', 'ヌ', 'ネ', 'ノ']],
        'ha' => ['label' => 'は', 'chars' => ['は', 'ば', 'ぱ', 'ひ', 'び', 'ぴ', 'ふ', 'ぶ', 'ぷ', 'へ', 'べ', 'ぺ',
                                              'ほ', 'ぼ', 'ぽ', 'ハ', 'バ', 'パ', 'ヒ', 'ビ', 'ピ', 'フ', 'ブ', 'プ',
                                              'ヘ', 'ベ', 'ペ', 'ホ', 'ボ', 'ポ']],
        'ma' => ['label' => 'ま', 'chars' => ['ま', 'み', 'む', 'め', 'も', 'マ', 'ミ', 'ム', 'メ', 'モ']],
        'ya' => ['label' => 'や', 'chars' => ['や', 'ゃ', 'ゆ', 'ゅ', 'よ', 'ょ', 'ヤ', 'ャ', 'ユ', 'ュ', 'ヨ', 'ョ']],
        'ra' => ['label' => 'ら', 'chars' => ['ら', 'り', 'る', 'れ', 'ろ', 'ラ', 'リ', 'ル', 'レ', 'ロ']],
        'wa' => ['label' => 'わ', 'chars' => ['わ', 'ゎ', 'ゐ', 'ゑ', 'を', 'ん', 'ワ', 'ヮ', 'ヰ', 'ヱ', 'ヲ', 'ン']],
    ];

    /** Row key reserved for users with no phonetic surname. */
    const OTHER = 'other';
}
