#!/bin/env python

### plot_thd.py
### Plot the THD of a circuit over a range of frequencies using ngspice.
###
### Author: Nathan Campos <nathanpc@dreamintech.net>

import os
import sys
import subprocess
from pylab import matplotlib

# Parses the THD from ngspice's output.
def grab_thd(result):
    return (result.split("THD: "))[1].split(" %")[0]

# Run the simulation, and returns an array with the THDs.
def run_simulations(circuit, var, rng, stdprint=False):
    thds = []

    for freq in rng:
        newcircuit = circuit.replace(var, str(freq) + "Hz")

        # Write temporary circuit.
        tmpfile = open("tmp_spice.cir", "w")
        tmpfile.write(newcircuit)
        tmpfile.close()

        # Execute ngspice and grep the THD line.
        ngspice = subprocess.Popen("ngspice < tmp_spice.cir 2>/dev/null | grep THD", shell=True, stdout=subprocess.PIPE)
        spice = ngspice.stdout.read()

        # Append the THD value to the list.
        thd = grab_thd(spice)
        thds.append(thd)

        # Requested to be printed to STDOUT instead of plotting.
        if (stdprint):
            print(str(freq) + "Hz: " + str(thd) + "%")

    # Remove the temporary file and return the list.
    os.remove("tmp_spice.cir")
    return thds

# Main program.
if __name__ == "__main__":
    filename = sys.argv[1]
    var = sys.argv[2]
    stdprint = False

    if (len(sys.argv) >= 4) and (sys.argv[3] == "-p"):
        stdprint = True

    # Grab the original circuit.
    circuit = open(filename, "r")
    contents = circuit.read()
    circuit.close()

    # Grab the THDs.
    rng = range(100, 20100, 100)
    thds = run_simulations(contents, var, rng, stdprint)

    # Plot.
    matplotlib.pyplot.plot(rng, thds)
    matplotlib.pyplot.xlabel("Frequency (Hz)")
    matplotlib.pyplot.ylabel("THD (%)")
    matplotlib.pyplot.title(contents.splitlines()[0])
    matplotlib.pyplot.grid(True)
    #matplotlib.pyplot.savefig("test.png")
    matplotlib.pyplot.show()