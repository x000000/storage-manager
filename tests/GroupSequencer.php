<?php

namespace x000000\StorageManager\tests;

class GroupSequencer implements \IteratorAggregate
{
	
	private $_groups = [];
	private $_sequences;
	
	public function __construct(... $args)
	{
		foreach ($args as $group) {
			$this->_groups[] = new \ArrayObject($group);
		}
	}
	
	private function getSequences() 
	{
		if ($this->_sequences === null) {
			for ($seqLen = 0, $maxLen = count($this->_groups); $seqLen < $maxLen; $seqLen++) {
				$sequences = $this->_sequences ?: [];
				$this->_sequences = [];

				foreach ($this->_groups as $group) {
					foreach ($sequences as $seq) {
						if (!in_array($group, $seq)) {
							$seq[] = $group;
							$this->_sequences[] = $seq;
						}
					}

					$this->_sequences[] = [ $group ];
				}
			}
		}

		return $this->_sequences;
	}

	public function getIterator()
	{
		$sequences = $this->getSequences();
		foreach ($sequences as $sequence) {
			$sequence    = array_map(function(\ArrayObject $group) { return $group->getIterator(); }, $sequence);
			$sequenceLen = count($sequence);
			$index       = 0;

			while (true) {
				yield array_map(function(\Iterator $group) { return [$group->key(), $group->current()]; }, $sequence);
				if (!$this->moveNext($sequence, $index, $sequenceLen)) {
					break;
				}
			}
		}
	}

	/**
	 * @param \Iterator[] $sequence
	 * @param int $index
	 * @param int $sequenceLen
	 * @return bool
	 */
	private function moveNext(&$sequence, &$index, $sequenceLen)
	{
		if (!$this->next($sequence[$index])) {
			$sequence[$index]->rewind();
			
			if (++$index < $sequenceLen) {
				if ($this->moveNext($sequence, $index, $sequenceLen)) {
					$index = 0;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		return true;
	}
	
	private function next(\Iterator $iterator) 
	{
		$iterator->next();
		return $iterator->valid();
	}

}
